<?php

declare(strict_types=1);

namespace AutoGen\Packages\Model;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputOption;

class ModelGeneratorCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'autogen:model
                            {--connection= : The database connection to use}
                            {--dir= : The subdirectory within app/Models/}
                            {--namespace= : Custom namespace for the models}
                            {--tables= : Comma-separated list of tables to generate}
                            {--all-tables : Generate models for all tables}
                            {--force : Overwrite existing model files}
                            {--with-relationships : Analyze and include relationships}
                            {--with-validation : Include validation rules}
                            {--with-scopes : Generate common query scopes}
                            {--with-traits : Auto-detect and include traits}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate Eloquent models from database tables';

    /**
     * The database introspector instance.
     *
     * @var DatabaseIntrospector
     */
    protected DatabaseIntrospector $introspector;

    /**
     * The model generator instance.
     *
     * @var ModelGenerator
     */
    protected ModelGenerator $generator;

    /**
     * The relationship analyzer instance.
     *
     * @var RelationshipAnalyzer
     */
    protected RelationshipAnalyzer $relationshipAnalyzer;

    /**
     * Create a new command instance.
     */
    public function __construct(
        DatabaseIntrospector $introspector,
        ModelGenerator $generator,
        RelationshipAnalyzer $relationshipAnalyzer
    ) {
        parent::__construct();
        
        $this->introspector = $introspector;
        $this->generator = $generator;
        $this->relationshipAnalyzer = $relationshipAnalyzer;
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

        $this->info("Using database connection: {$connection}");

        // Get configuration
        $config = $this->getConfiguration();

        // Get tables to generate
        $tables = $this->getTables($connection);

        if (empty($tables)) {
            $this->warn('No tables found to generate models for.');
            return self::SUCCESS;
        }

        $this->info('Found ' . count($tables) . ' table(s) to process.');

        // Analyze relationships if requested
        $relationships = [];
        if ($this->option('with-relationships')) {
            $this->info('Analyzing database relationships...');
            $relationships = $this->relationshipAnalyzer->analyze($connection, $tables);
        }

        // Generate models
        $generated = 0;
        $skipped = 0;

        foreach ($tables as $table) {
            $this->info("Processing table: {$table}");

            // Check if model already exists
            $modelPath = $this->generator->getModelPath($table, $config);
            
            if (file_exists($modelPath) && !$this->option('force')) {
                if (!$this->confirm("Model {$modelPath} already exists. Overwrite?")) {
                    $this->warn("Skipped: {$table}");
                    $skipped++;
                    continue;
                }
            }

            try {
                // Introspect table structure
                $tableStructure = $this->introspector->introspectTable($connection, $table);
                
                // Generate model
                $this->generator->generate(
                    $connection,
                    $table,
                    $tableStructure,
                    $relationships[$table] ?? [],
                    $config
                );
                
                $this->info("✓ Generated model for: {$table}");
                $generated++;
            } catch (\Exception $e) {
                $this->error("Failed to generate model for {$table}: " . $e->getMessage());
                if ($this->option('verbose')) {
                    $this->error($e->getTraceAsString());
                }
            }
        }

        $this->newLine();
        $this->info("Generation complete! Generated: {$generated}, Skipped: {$skipped}");

        return self::SUCCESS;
    }

    /**
     * Get configuration options.
     */
    protected function getConfiguration(): array
    {
        return [
            'dir' => $this->option('dir') ?? '',
            'namespace' => $this->option('namespace'),
            'force' => $this->option('force') ?? false,
            'with_relationships' => $this->option('with-relationships') ?? false,
            'with_validation' => $this->option('with-validation') ?? false,
            'with_scopes' => $this->option('with-scopes') ?? false,
            'with_traits' => $this->option('with-traits') ?? true,
        ];
    }

    /**
     * Get the list of tables to generate models for.
     */
    protected function getTables(string $connection): array
    {
        if ($this->option('tables')) {
            // Use specified tables
            $tables = array_map('trim', explode(',', $this->option('tables')));
        } else {
            // Get all tables from the database
            $tables = $this->introspector->getTables($connection);
        }

        // Filter out ignored tables
        $ignoredTables = config('autogen.tables_to_ignore', []);
        
        return array_values(array_filter($tables, function ($table) use ($ignoredTables) {
            return !in_array($table, $ignoredTables);
        }));
    }
}