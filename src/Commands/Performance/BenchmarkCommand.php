<?php

declare(strict_types=1);

namespace AutoGen\Commands\Performance;

use AutoGen\Common\Performance\PerformanceMonitor;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

/**
 * Performance benchmarking command
 */
class BenchmarkCommand extends Command
{
    protected $signature = 'autogen:benchmark 
                          {--operation=* : Specific operations to benchmark}
                          {--iterations=10 : Number of iterations to run}
                          {--warmup=3 : Number of warmup iterations}
                          {--output= : Output format (table|json|csv)}
                          {--baseline= : Compare against baseline file}
                          {--save-baseline : Save results as new baseline}';

    protected $description = 'Run performance benchmarks for AutoGen operations';

    protected PerformanceMonitor $monitor;
    protected array $benchmarks = [];

    public function __construct()
    {
        parent::__construct();
        $this->monitor = new PerformanceMonitor(config('autogen.performance.monitoring', []));
    }

    public function handle(): int
    {
        $this->info('🚀 Starting AutoGen Performance Benchmarks');
        $this->line('');

        $operations = $this->option('operation') ?: $this->getDefaultOperations();
        $iterations = (int) $this->option('iterations');
        $warmupIterations = (int) $this->option('warmup');

        // Run warmup iterations
        if ($warmupIterations > 0) {
            $this->info("🔥 Running {$warmupIterations} warmup iterations...");
            $this->runWarmup($operations, $warmupIterations);
            $this->line('');
        }

        // Run actual benchmarks
        $this->info("⏱️  Running {$iterations} benchmark iterations...");
        $results = $this->runBenchmarks($operations, $iterations);

        // Display results
        $this->displayResults($results);

        // Compare with baseline if requested
        if ($baselineFile = $this->option('baseline')) {
            $this->compareWithBaseline($results, $baselineFile);
        }

        // Save as baseline if requested
        if ($this->option('save-baseline')) {
            $this->saveBaseline($results);
        }

        $this->info('✅ Benchmarks completed successfully!');
        return Command::SUCCESS;
    }

    /**
     * Get default operations to benchmark.
     */
    protected function getDefaultOperations(): array
    {
        return [
            'schema_introspection',
            'template_compilation',
            'model_generation',
            'controller_generation',
            'view_generation',
            'database_queries',
            'cache_operations',
        ];
    }

    /**
     * Run warmup iterations.
     */
    protected function runWarmup(array $operations, int $iterations): void
    {
        $progressBar = $this->output->createProgressBar(count($operations) * $iterations);
        $progressBar->start();

        for ($i = 0; $i < $iterations; $i++) {
            foreach ($operations as $operation) {
                $this->runSingleBenchmark($operation, false);
                $progressBar->advance();
            }
        }

        $progressBar->finish();
        $this->line('');
    }

    /**
     * Run benchmark iterations.
     */
    protected function runBenchmarks(array $operations, int $iterations): array
    {
        $results = [];
        $progressBar = $this->output->createProgressBar(count($operations) * $iterations);
        $progressBar->start();

        foreach ($operations as $operation) {
            $results[$operation] = [
                'times' => [],
                'memory' => [],
                'queries' => [],
            ];

            for ($i = 0; $i < $iterations; $i++) {
                $result = $this->runSingleBenchmark($operation, true);
                
                $results[$operation]['times'][] = $result['execution_time'];
                $results[$operation]['memory'][] = $result['memory_usage'];
                $results[$operation]['queries'][] = $result['query_count'];
                
                $progressBar->advance();
            }
        }

        $progressBar->finish();
        $this->line('');

        return $this->calculateStatistics($results);
    }

    /**
     * Run a single benchmark operation.
     */
    protected function runSingleBenchmark(string $operation, bool $record = true): array
    {
        $operationId = $this->monitor->startOperation($operation, ['benchmark' => true]);
        
        switch ($operation) {
            case 'schema_introspection':
                $this->benchmarkSchemaIntrospection();
                break;
            case 'template_compilation':
                $this->benchmarkTemplateCompilation();
                break;
            case 'model_generation':
                $this->benchmarkModelGeneration();
                break;
            case 'controller_generation':
                $this->benchmarkControllerGeneration();
                break;
            case 'view_generation':
                $this->benchmarkViewGeneration();
                break;
            case 'database_queries':
                $this->benchmarkDatabaseQueries();
                break;
            case 'cache_operations':
                $this->benchmarkCacheOperations();
                break;
            default:
                // Custom operation
                $this->benchmarkCustomOperation($operation);
        }

        return $this->monitor->endOperation($operationId);
    }

    /**
     * Benchmark schema introspection.
     */
    protected function benchmarkSchemaIntrospection(): void
    {
        try {
            $connection = DB::getDefaultConnection();
            $schema = DB::connection($connection)->getDoctrineSchemaManager();
            
            // Get table names
            $tables = $schema->listTableNames();
            
            // Introspect a few tables
            $tablesToCheck = array_slice($tables, 0, min(5, count($tables)));
            foreach ($tablesToCheck as $table) {
                $schema->introspectTable($table);
            }
        } catch (\Exception $e) {
            // Ignore errors during benchmarking
        }
    }

    /**
     * Benchmark template compilation.
     */
    protected function benchmarkTemplateCompilation(): void
    {
        $stubsPath = __DIR__ . '/../../Packages/Model/Stubs';
        
        if (File::exists($stubsPath)) {
            $stubs = File::glob($stubsPath . '/*.stub');
            
            foreach (array_slice($stubs, 0, 3) as $stub) {
                $content = File::get($stub);
                $compiled = str_replace(['{{ model }}', '{{ namespace }}'], ['TestModel', 'App\\Models'], $content);
                // Simulate some processing
                strlen($compiled);
            }
        }
    }

    /**
     * Benchmark model generation.
     */
    protected function benchmarkModelGeneration(): void
    {
        // Simulate model generation process
        $modelData = [
            'name' => 'BenchmarkModel',
            'namespace' => 'App\\Models',
            'fillable' => ['name', 'email', 'active'],
            'casts' => ['active' => 'boolean', 'created_at' => 'datetime'],
            'relationships' => [
                'posts' => ['type' => 'hasMany', 'model' => 'Post'],
                'profile' => ['type' => 'hasOne', 'model' => 'Profile'],
            ],
        ];
        
        // Simulate processing
        $serialized = serialize($modelData);
        $unserialized = unserialize($serialized);
        json_encode($unserialized);
    }

    /**
     * Benchmark controller generation.
     */
    protected function benchmarkControllerGeneration(): void
    {
        // Simulate controller generation
        $controllerData = [
            'name' => 'BenchmarkController',
            'model' => 'BenchmarkModel',
            'resource' => true,
            'api' => false,
            'middleware' => ['auth'],
        ];
        
        // Simulate processing
        foreach (['index', 'show', 'create', 'store', 'edit', 'update', 'destroy'] as $method) {
            $methodData = array_merge($controllerData, ['method' => $method]);
            json_encode($methodData);
        }
    }

    /**
     * Benchmark view generation.
     */
    protected function benchmarkViewGeneration(): void
    {
        // Simulate view generation
        $viewData = [
            'model' => 'BenchmarkModel',
            'fields' => ['name', 'email', 'active'],
            'framework' => 'tailwind',
        ];
        
        // Simulate processing different view types
        foreach (['index', 'show', 'create', 'edit'] as $view) {
            $data = array_merge($viewData, ['view_type' => $view]);
            json_encode($data);
        }
    }

    /**
     * Benchmark database queries.
     */
    protected function benchmarkDatabaseQueries(): void
    {
        try {
            // Simple queries to benchmark database performance
            DB::select('SELECT 1');
            DB::select('SELECT COUNT(*) FROM information_schema.tables');
            
            // Simulate some table operations if possible
            $connection = DB::getDefaultConnection();
            $driver = DB::connection($connection)->getDriverName();
            
            switch ($driver) {
                case 'mysql':
                    DB::select('SHOW TABLES');
                    break;
                case 'pgsql':
                    DB::select('SELECT tablename FROM pg_tables WHERE schemaname = ?', ['public']);
                    break;
                case 'sqlite':
                    DB::select('SELECT name FROM sqlite_master WHERE type = ?', ['table']);
                    break;
            }
        } catch (\Exception $e) {
            // Ignore errors during benchmarking
        }
    }

    /**
     * Benchmark cache operations.
     */
    protected function benchmarkCacheOperations(): void
    {
        $key = 'autogen_benchmark_' . uniqid();
        $data = ['test' => 'data', 'timestamp' => now()->toISOString()];
        
        // Cache operations
        cache()->put($key, $data, 60);
        cache()->get($key);
        cache()->forget($key);
        
        // Tagged cache operations
        cache()->tags(['autogen', 'benchmark'])->put($key, $data, 60);
        cache()->tags(['autogen', 'benchmark'])->get($key);
        cache()->tags(['autogen', 'benchmark'])->flush();
    }

    /**
     * Benchmark custom operation.
     */
    protected function benchmarkCustomOperation(string $operation): void
    {
        // Default implementation for custom operations
        usleep(100); // Simulate some work
    }

    /**
     * Calculate statistics from raw results.
     */
    protected function calculateStatistics(array $results): array
    {
        $statistics = [];
        
        foreach ($results as $operation => $data) {
            $times = $data['times'];
            $memory = $data['memory'];
            $queries = $data['queries'];
            
            $statistics[$operation] = [
                'execution_time' => [
                    'mean' => array_sum($times) / count($times),
                    'median' => $this->median($times),
                    'min' => min($times),
                    'max' => max($times),
                    'std_dev' => $this->standardDeviation($times),
                ],
                'memory_usage' => [
                    'mean' => array_sum($memory) / count($memory),
                    'median' => $this->median($memory),
                    'min' => min($memory),
                    'max' => max($memory),
                ],
                'query_count' => [
                    'mean' => array_sum($queries) / count($queries),
                    'median' => $this->median($queries),
                    'min' => min($queries),
                    'max' => max($queries),
                ],
                'iterations' => count($times),
            ];
        }
        
        return $statistics;
    }

    /**
     * Display benchmark results.
     */
    protected function displayResults(array $results): void
    {
        $outputFormat = $this->option('output', 'table');
        
        switch ($outputFormat) {
            case 'json':
                $this->line(json_encode($results, JSON_PRETTY_PRINT));
                break;
            case 'csv':
                $this->outputCsv($results);
                break;
            default:
                $this->outputTable($results);
        }
    }

    /**
     * Display results as table.
     */
    protected function outputTable(array $results): void
    {
        $headers = ['Operation', 'Mean Time (ms)', 'Memory (KB)', 'Queries', 'Min/Max Time'];
        $rows = [];
        
        foreach ($results as $operation => $stats) {
            $rows[] = [
                $operation,
                number_format($stats['execution_time']['mean'] * 1000, 2),
                number_format($stats['memory_usage']['mean'] / 1024, 1),
                number_format($stats['query_count']['mean'], 1),
                number_format($stats['execution_time']['min'] * 1000, 2) . '/' . 
                number_format($stats['execution_time']['max'] * 1000, 2),
            ];
        }
        
        $this->table($headers, $rows);
    }

    /**
     * Output results as CSV.
     */
    protected function outputCsv(array $results): void
    {
        $this->line('Operation,Mean Time (s),Min Time (s),Max Time (s),Mean Memory (bytes),Mean Queries');
        
        foreach ($results as $operation => $stats) {
            $this->line(implode(',', [
                $operation,
                $stats['execution_time']['mean'],
                $stats['execution_time']['min'],
                $stats['execution_time']['max'],
                $stats['memory_usage']['mean'],
                $stats['query_count']['mean'],
            ]));
        }
    }

    /**
     * Compare with baseline results.
     */
    protected function compareWithBaseline(array $results, string $baselineFile): void
    {
        if (!File::exists($baselineFile)) {
            $this->warn("Baseline file not found: {$baselineFile}");
            return;
        }

        $baseline = json_decode(File::get($baselineFile), true);
        if (!$baseline) {
            $this->warn("Invalid baseline file format");
            return;
        }

        $this->info('');
        $this->info('📊 Comparison with Baseline:');
        
        $headers = ['Operation', 'Current (ms)', 'Baseline (ms)', 'Change', 'Status'];
        $rows = [];
        
        foreach ($results as $operation => $stats) {
            if (!isset($baseline[$operation])) {
                continue;
            }
            
            $currentTime = $stats['execution_time']['mean'] * 1000;
            $baselineTime = $baseline[$operation]['execution_time']['mean'] * 1000;
            $change = (($currentTime - $baselineTime) / $baselineTime) * 100;
            
            $status = '✅ OK';
            if ($change > 20) {
                $status = '❌ SLOWER';
            } elseif ($change < -20) {
                $status = '🚀 FASTER';
            }
            
            $rows[] = [
                $operation,
                number_format($currentTime, 2),
                number_format($baselineTime, 2),
                ($change > 0 ? '+' : '') . number_format($change, 1) . '%',
                $status,
            ];
        }
        
        $this->table($headers, $rows);
    }

    /**
     * Save results as baseline.
     */
    protected function saveBaseline(array $results): void
    {
        $baselineDir = storage_path('app/autogen/benchmarks');
        
        if (!File::exists($baselineDir)) {
            File::makeDirectory($baselineDir, 0755, true);
        }
        
        $filename = $baselineDir . '/baseline_' . date('Y-m-d_H-i-s') . '.json';
        
        $baselineData = [
            'created_at' => now()->toISOString(),
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'results' => $results,
        ];
        
        File::put($filename, json_encode($baselineData, JSON_PRETTY_PRINT));
        
        $this->info("Baseline saved to: {$filename}");
    }

    /**
     * Calculate median value.
     */
    protected function median(array $values): float
    {
        sort($values);
        $count = count($values);
        
        if ($count % 2 === 0) {
            return ($values[$count / 2 - 1] + $values[$count / 2]) / 2;
        }
        
        return $values[floor($count / 2)];
    }

    /**
     * Calculate standard deviation.
     */
    protected function standardDeviation(array $values): float
    {
        $mean = array_sum($values) / count($values);
        $variance = array_sum(array_map(fn($x) => pow($x - $mean, 2), $values)) / count($values);
        
        return sqrt($variance);
    }
}