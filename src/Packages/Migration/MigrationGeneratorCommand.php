<?php

declare(strict_types=1);

namespace AutoGen\Packages\Migration;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputOption;

class MigrationGeneratorCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'autogen:migration
                            {--connection= : The database connection to use}
                            {--table= : Generate migration for a specific table}
                            {--all-tables : Generate migrations for all tables}
                            {--output-path= : Custom output path for migrations}
                            {--force : Overwrite existing migration files}
                            {--preserve-order : Preserve table creation order based on foreign keys}
                            {--with-foreign-keys : Include foreign key constraints}
                            {--with-indexes : Include all indexes}
                            {--skip-views : Skip database views}
                            {--rollback-support : Generate rollback methods}
                            {--timestamp-prefix : Add timestamp prefix to migrations}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reverse engineer existing database schemas to Laravel migrations';

    /**
     * The database schema analyzer instance.
     *
     * @var DatabaseSchemaAnalyzer
     */
    protected DatabaseSchemaAnalyzer $analyzer;

    /**
     * The migration generator instance.
     *
     * @var MigrationGenerator
     */
    protected MigrationGenerator $generator;

    /**
     * Create a new command instance.
     */
    public function __construct(
        DatabaseSchemaAnalyzer $analyzer,
        MigrationGenerator $generator
    ) {
        parent::__construct();
        
        $this->analyzer = $analyzer;
        $this->generator = $generator;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $connection = $this->option('connection');
        
        if (!$connection) {
            $this->error('The --connection option is required.');
            return self::FAILURE;
        }

        $this->info("Analyzing database connection: {$connection}");

        try {
            // Validate connection
            $this->analyzer->validateConnection($connection);
        } catch (\Exception $e) {
            $this->error("Failed to connect to database: " . $e->getMessage());
            return self::FAILURE;
        }

        // Get configuration
        $config = $this->getConfiguration();

        // Get tables to generate migrations for
        $tables = $this->getTables($connection, $config);

        if (empty($tables)) {
            $this->warn('No tables found to generate migrations for.');
            return self::SUCCESS;
        }

        $this->info('Found ' . count($tables) . ' table(s) to process.');

        // Analyze database schema
        $this->info('Analyzing database schema...');
        $schema = $this->analyzer->analyzeSchema($connection, $tables, $config);

        // Order tables by dependencies if preserve-order is enabled
        if ($config['preserve_order']) {
            $this->info('Ordering tables by foreign key dependencies...');
            $orderedTables = $this->analyzer->orderTablesByDependencies($schema);
        } else {
            $orderedTables = array_keys($schema['tables']);
        }

        // Generate migrations
        $generated = 0;
        $skipped = 0;

        foreach ($orderedTables as $table) {
            $this->info("Processing table: {$table}");

            try {
                $result = $this->generator->generateMigration(
                    $connection,
                    $table,
                    $schema['tables'][$table],
                    $schema,
                    $config
                );

                if ($result['generated']) {
                    $this->info("✓ Generated migration for: {$table} -> {$result['file']}");
                    $generated++;
                } else {
                    $this->warn("Skipped: {$table} - {$result['reason']}");
                    $skipped++;
                }
            } catch (\Exception $e) {
                $this->error("Failed to generate migration for {$table}: " . $e->getMessage());
                if ($this->option('verbose')) {
                    $this->error($e->getTraceAsString());
                }
            }
        }

        $this->newLine();
        $this->info("Migration generation complete!");
        $this->info("Generated: {$generated}, Skipped: {$skipped}");

        if ($generated > 0) {
            $this->newLine();
            $this->info("Next steps:");
            $this->line("1. Review the generated migration files");
            $this->line("2. Run 'php artisan migrate' to apply the migrations");
            $this->line("3. Test rollback with 'php artisan migrate:rollback'");
        }

        return self::SUCCESS;
    }

    /**
     * Get configuration options.
     */
    protected function getConfiguration(): array
    {
        return [
            'table' => $this->option('table'),
            'all_tables' => $this->option('all-tables') ?? false,
            'output_path' => $this->option('output-path') ?? database_path('migrations'),
            'force' => $this->option('force') ?? false,
            'preserve_order' => $this->option('preserve-order') ?? true,
            'with_foreign_keys' => $this->option('with-foreign-keys') ?? true,
            'with_indexes' => $this->option('with-indexes') ?? true,
            'skip_views' => $this->option('skip-views') ?? true,
            'rollback_support' => $this->option('rollback-support') ?? true,
            'timestamp_prefix' => $this->option('timestamp-prefix') ?? true,
        ];
    }

    /**
     * Get the list of tables to generate migrations for.
     */
    protected function getTables(string $connection, array $config): array
    {
        if ($config['table']) {
            // Single table specified
            return [$config['table']];
        }

        if ($config['all_tables']) {
            // Get all tables from the database
            $tables = $this->analyzer->getAllTables($connection);
        } else {
            // Interactive table selection
            $availableTables = $this->analyzer->getAllTables($connection);
            
            if (empty($availableTables)) {
                return [];
            }

            $this->info('Available tables:');
            foreach ($availableTables as $index => $table) {
                $this->line("  {$index}: {$table}");
            }

            $selectedIndices = $this->ask('Enter table indices to generate (comma-separated, or "all" for all tables)');

            if (strtolower(trim($selectedIndices)) === 'all') {
                $tables = $availableTables;
            } else {
                $indices = array_map('trim', explode(',', $selectedIndices));
                $tables = [];
                foreach ($indices as $index) {
                    if (is_numeric($index) && isset($availableTables[$index])) {
                        $tables[] = $availableTables[$index];
                    }
                }
            }
        }

        // Filter out ignored tables
        $ignoredTables = config('autogen.tables_to_ignore', [
            'migrations',
            'failed_jobs',
            'password_resets',
            'password_reset_tokens',
            'personal_access_tokens',
        ]);
        
        return array_values(array_filter($tables, function ($table) use ($ignoredTables) {
            return !in_array($table, $ignoredTables);
        }));
    }

    /**
     * Get the options for the command.
     */
    protected function getOptions(): array
    {
        return [
            ['connection', null, InputOption::VALUE_REQUIRED, 'The database connection to use'],
            ['table', null, InputOption::VALUE_OPTIONAL, 'Generate migration for a specific table'],
            ['all-tables', null, InputOption::VALUE_NONE, 'Generate migrations for all tables'],
            ['output-path', null, InputOption::VALUE_OPTIONAL, 'Custom output path for migrations'],
            ['force', null, InputOption::VALUE_NONE, 'Overwrite existing migration files'],
            ['preserve-order', null, InputOption::VALUE_NONE, 'Preserve table creation order based on foreign keys'],
            ['with-foreign-keys', null, InputOption::VALUE_NONE, 'Include foreign key constraints'],
            ['with-indexes', null, InputOption::VALUE_NONE, 'Include all indexes'],
            ['skip-views', null, InputOption::VALUE_NONE, 'Skip database views'],
            ['rollback-support', null, InputOption::VALUE_NONE, 'Generate rollback methods'],
            ['timestamp-prefix', null, InputOption::VALUE_NONE, 'Add timestamp prefix to migrations'],
        ];
    }
}