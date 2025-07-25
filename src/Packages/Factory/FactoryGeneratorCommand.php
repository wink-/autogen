<?php

declare(strict_types=1);

namespace AutoGen\Packages\Factory;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class FactoryGeneratorCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'autogen:factory
                            {model : The name of the model}
                            {--with-states : Generate state methods for common scenarios}
                            {--with-relationships : Include factory methods for relationships}
                            {--count=10 : Default count for factory generation}
                            {--force : Overwrite existing factory files}
                            {--locale=en_US : Locale for fake data generation}
                            {--template=default : Template complexity level (minimal, default, advanced)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate model factories with intelligent fake data mapping';

    /**
     * The factory generator instance.
     *
     * @var FactoryGenerator
     */
    protected FactoryGenerator $generator;

    /**
     * Create a new command instance.
     */
    public function __construct(FactoryGenerator $generator)
    {
        parent::__construct();
        $this->generator = $generator;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $modelName = $this->argument('model');
        
        if (!$modelName) {
            $this->error('Model name is required.');
            return self::FAILURE;
        }

        // Normalize model name
        $modelName = Str::studly($modelName);
        
        $this->info("Generating factory for model: {$modelName}");

        // Get configuration options
        $config = $this->getConfiguration();

        // Check if factory already exists
        $factoryPath = $this->generator->getFactoryPath($modelName);
        
        if (file_exists($factoryPath) && !$this->option('force')) {
            if (!$this->confirm("Factory {$factoryPath} already exists. Overwrite?")) {
                $this->warn("Factory generation cancelled.");
                return self::SUCCESS;
            }
        }

        try {
            // Generate the factory
            $result = $this->generator->generate($modelName, $config);
            
            if ($result['success']) {
                $this->info("✓ Factory generated successfully: {$result['path']}");
                
                if ($config['with_states'] && !empty($result['states'])) {
                    $this->info("✓ Generated states: " . implode(', ', $result['states']));
                }
                
                if ($config['with_relationships'] && !empty($result['relationships'])) {
                    $this->info("✓ Generated relationship methods: " . implode(', ', $result['relationships']));
                }
                
                // Show usage examples
                $this->showUsageExamples($modelName, $config);
                
                return self::SUCCESS;
            } else {
                $this->error("Failed to generate factory: " . $result['error']);
                return self::FAILURE;
            }
            
        } catch (\Exception $e) {
            $this->error("Factory generation failed: " . $e->getMessage());
            
            if ($this->option('verbose')) {
                $this->error($e->getTraceAsString());
            }
            
            return self::FAILURE;
        }
    }

    /**
     * Get configuration options from command arguments.
     */
    protected function getConfiguration(): array
    {
        return [
            'with_states' => $this->option('with-states') ?? false,
            'with_relationships' => $this->option('with-relationships') ?? false,
            'count' => (int) ($this->option('count') ?? 10),
            'force' => $this->option('force') ?? false,
            'locale' => $this->option('locale') ?? 'en_US',
            'template' => $this->option('template') ?? 'default',
        ];
    }

    /**
     * Show usage examples for the generated factory.
     */
    protected function showUsageExamples(string $modelName, array $config): void
    {
        $this->newLine();
        $this->info("Usage examples:");
        $this->line("// Create a single {$modelName}");
        $this->line("{$modelName}::factory()->create();");
        
        $this->newLine();
        $this->line("// Create multiple {$modelName}s");
        $this->line("{$modelName}::factory()->count({$config['count']})->create();");
        
        if ($config['with_states']) {
            $this->newLine();
            $this->line("// Using states (examples)");
            $this->line("{$modelName}::factory()->active()->create();");
            $this->line("{$modelName}::factory()->inactive()->create();");
        }
        
        if ($config['with_relationships']) {
            $this->newLine();
            $this->line("// With relationships");
            $this->line("{$modelName}::factory()->withRelationships()->create();");
        }
        
        $this->newLine();
        $this->line("// Override specific attributes");
        $this->line("{$modelName}::factory()->create(['name' => 'Custom Name']);");
    }

    /**
     * Get the arguments provided to the command.
     */
    protected function getArguments(): array
    {
        return [
            ['model', InputArgument::REQUIRED, 'The name of the model'],
        ];
    }

    /**
     * Get the options provided to the command.
     */
    protected function getOptions(): array
    {
        return [
            ['with-states', null, InputOption::VALUE_NONE, 'Generate state methods for common scenarios'],
            ['with-relationships', null, InputOption::VALUE_NONE, 'Include factory methods for relationships'],
            ['count', 'c', InputOption::VALUE_OPTIONAL, 'Default count for factory generation', 10],
            ['force', 'f', InputOption::VALUE_NONE, 'Overwrite existing factory files'],
            ['locale', 'l', InputOption::VALUE_OPTIONAL, 'Locale for fake data generation', 'en_US'],
            ['template', 't', InputOption::VALUE_OPTIONAL, 'Template complexity level (minimal, default, advanced)', 'default'],
        ];
    }
}