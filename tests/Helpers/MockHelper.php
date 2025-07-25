<?php

declare(strict_types=1);

namespace AutoGen\Tests\Helpers;

use Mockery;
use Mockery\MockInterface;

class MockHelper
{
    /**
     * Create a mock database introspector
     */
    public static function mockDatabaseIntrospector(): MockInterface
    {
        $mock = Mockery::mock('AutoGen\Packages\Model\DatabaseIntrospector');
        
        $mock->shouldReceive('getTableNames')
            ->andReturn(['users', 'posts', 'categories']);
        
        $mock->shouldReceive('getTableSchema')
            ->with('users')
            ->andReturn([
                'id' => ['type' => 'bigint', 'nullable' => false, 'primary' => true, 'autoIncrement' => true],
                'name' => ['type' => 'varchar', 'nullable' => false, 'length' => 255],
                'email' => ['type' => 'varchar', 'nullable' => false, 'length' => 255, 'unique' => true],
                'password' => ['type' => 'varchar', 'nullable' => false, 'length' => 255],
                'created_at' => ['type' => 'timestamp', 'nullable' => true],
                'updated_at' => ['type' => 'timestamp', 'nullable' => true],
            ]);
        
        $mock->shouldReceive('getTableSchema')
            ->with('posts')
            ->andReturn([
                'id' => ['type' => 'bigint', 'nullable' => false, 'primary' => true, 'autoIncrement' => true],
                'title' => ['type' => 'varchar', 'nullable' => false, 'length' => 255],
                'content' => ['type' => 'text', 'nullable' => false],
                'user_id' => ['type' => 'bigint', 'nullable' => false, 'foreign' => 'users.id'],
                'created_at' => ['type' => 'timestamp', 'nullable' => true],
                'updated_at' => ['type' => 'timestamp', 'nullable' => true],
            ]);
        
        $mock->shouldReceive('getForeignKeys')
            ->with('posts')
            ->andReturn([
                'user_id' => ['table' => 'users', 'column' => 'id'],
            ]);
        
        return $mock;
    }

    /**
     * Create a mock relationship analyzer
     */
    public static function mockRelationshipAnalyzer(): MockInterface
    {
        $mock = Mockery::mock('AutoGen\Packages\Model\RelationshipAnalyzer');
        
        $mock->shouldReceive('analyzeRelationships')
            ->with('users')
            ->andReturn([
                'hasMany' => [
                    ['model' => 'Post', 'foreign_key' => 'user_id', 'local_key' => 'id'],
                ],
                'belongsTo' => [],
                'belongsToMany' => [],
                'hasOne' => [],
            ]);
        
        $mock->shouldReceive('analyzeRelationships')
            ->with('posts')
            ->andReturn([
                'hasMany' => [],
                'belongsTo' => [
                    ['model' => 'User', 'foreign_key' => 'user_id', 'local_key' => 'id'],
                ],
                'belongsToMany' => [],
                'hasOne' => [],
            ]);
        
        return $mock;
    }

    /**
     * Create a mock template engine
     */
    public static function mockTemplateEngine(): MockInterface
    {
        $mock = Mockery::mock('AutoGen\Common\Templates\TemplateEngine');
        
        $mock->shouldReceive('render')
            ->andReturnUsing(function ($template, $variables) {
                // Simple template rendering for testing
                $content = "<?php\n\nclass {$variables['className']}\n{\n    // Generated content\n}";
                return $content;
            });
        
        return $mock;
    }

    /**
     * Create a mock code formatter
     */
    public static function mockCodeFormatter(): MockInterface
    {
        $mock = Mockery::mock('AutoGen\Common\Formatting\CodeFormatter');
        
        $mock->shouldReceive('format')
            ->andReturnUsing(function ($code) {
                // Simple formatting for testing
                return trim($code) . "\n";
            });
        
        return $mock;
    }

    /**
     * Create a mock AI provider
     */
    public static function mockAIProvider(): MockInterface
    {
        $mock = Mockery::mock('AutoGen\Common\Contracts\AIProviderInterface');
        
        $mock->shouldReceive('generateCode')
            ->andReturn('<?php // AI generated code');
        
        $mock->shouldReceive('optimizeCode')
            ->andReturnUsing(function ($code) {
                return $code . ' // Optimized by AI';
            });
        
        $mock->shouldReceive('isAvailable')
            ->andReturn(true);
        
        return $mock;
    }

    /**
     * Create a mock file system
     */
    public static function mockFileSystem(): MockInterface
    {
        $mock = Mockery::mock('Illuminate\Filesystem\Filesystem');
        
        $mock->shouldReceive('exists')
            ->andReturn(false);
        
        $mock->shouldReceive('put')
            ->andReturn(true);
        
        $mock->shouldReceive('get')
            ->andReturn('<?php // Mock file content');
        
        $mock->shouldReceive('ensureDirectoryExists')
            ->andReturn(true);
        
        return $mock;
    }

    /**
     * Create mock configuration
     */
    public static function mockConfiguration(): array
    {
        return [
            'autogen' => [
                'defaults' => [
                    'controller_type' => 'resource',
                    'pagination' => 15,
                    'with_validation' => true,
                    'with_policy' => false,
                ],
                'namespaces' => [
                    'models' => 'App\\Models',
                    'controllers' => 'App\\Http\\Controllers',
                    'requests' => 'App\\Http\\Requests',
                    'policies' => 'App\\Policies',
                    'resources' => 'App\\Http\\Resources',
                ],
                'paths' => [
                    'models' => app_path('Models'),
                    'controllers' => app_path('Http/Controllers'),
                    'requests' => app_path('Http/Requests'),
                    'policies' => app_path('Policies'),
                    'resources' => app_path('Http/Resources'),
                    'factories' => database_path('factories'),
                    'migrations' => database_path('migrations'),
                    'views' => resource_path('views'),
                ],
            ],
            'datatable' => [
                'type' => 'yajra',
                'pagination' => 25,
                'export' => [
                    'enabled' => true,
                    'formats' => ['excel', 'csv', 'pdf'],
                ],
            ],
            'factory' => [
                'type' => 'advanced',
                'relationships' => true,
                'files' => false,
            ],
            'views' => [
                'framework' => 'bootstrap',
                'generate_forms' => true,
                'generate_lists' => true,
            ],
        ];
    }

    /**
     * Create a mock console command
     */
    public static function mockConsoleCommand(): MockInterface
    {
        $mock = Mockery::mock('Illuminate\Console\Command');
        
        $mock->shouldReceive('info')
            ->andReturnSelf();
        
        $mock->shouldReceive('error')
            ->andReturnSelf();
        
        $mock->shouldReceive('warn')
            ->andReturnSelf();
        
        $mock->shouldReceive('line')
            ->andReturnSelf();
        
        $mock->shouldReceive('confirm')
            ->andReturn(true);
        
        $mock->shouldReceive('choice')
            ->andReturn('resource');
        
        $mock->shouldReceive('ask')
            ->andReturn('TestModel');
        
        return $mock;
    }

    /**
     * Create mock database connection
     */
    public static function mockDatabaseConnection(): MockInterface
    {
        $mock = Mockery::mock('Illuminate\Database\Connection');
        
        $mock->shouldReceive('getDoctrineSchemaManager')
            ->andReturn(self::mockSchemaManager());
        
        $mock->shouldReceive('table')
            ->andReturnSelf();
        
        $mock->shouldReceive('select')
            ->andReturn([]);
        
        return $mock;
    }

    /**
     * Create mock schema manager
     */
    public static function mockSchemaManager(): MockInterface
    {
        $mock = Mockery::mock('Doctrine\DBAL\Schema\AbstractSchemaManager');
        
        $mock->shouldReceive('listTableNames')
            ->andReturn(['users', 'posts', 'categories']);
        
        $mock->shouldReceive('listTableColumns')
            ->andReturn([]);
        
        $mock->shouldReceive('listTableForeignKeys')
            ->andReturn([]);
        
        return $mock;
    }

    /**
     * Clean up all mocks
     */
    public static function cleanup(): void
    {
        Mockery::close();
    }
}