<?php

declare(strict_types=1);

namespace AutoGen\Packages\Migration\Tests;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use AutoGen\Packages\Migration\DatabaseSchemaAnalyzer;
use AutoGen\Packages\Migration\MigrationGenerator;
use AutoGen\Packages\Migration\MigrationTemplateEngine;
use AutoGen\Packages\Migration\MigrationGeneratorCommand;

class MigrationGeneratorTest extends TestCase
{
    use RefreshDatabase;

    protected DatabaseSchemaAnalyzer $analyzer;
    protected MigrationGenerator $generator;
    protected MigrationTemplateEngine $templateEngine;
    protected string $testOutputPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->analyzer = app(DatabaseSchemaAnalyzer::class);
        $this->generator = app(MigrationGenerator::class);
        $this->templateEngine = app(MigrationTemplateEngine::class);
        
        // Create temporary output directory for tests
        $this->testOutputPath = storage_path('framework/testing/migrations');
        if (!File::isDirectory($this->testOutputPath)) {
            File::makeDirectory($this->testOutputPath, 0755, true);
        }
    }

    protected function tearDown(): void
    {
        // Clean up test migration files
        if (File::isDirectory($this->testOutputPath)) {
            File::deleteDirectory($this->testOutputPath);
        }

        parent::tearDown();
    }

    /** @test */
    public function it_can_analyze_database_schema()
    {
        // Create a test table
        Schema::create('test_users', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });

        $schema = $this->analyzer->analyzeSchema('testing', ['test_users'], [
            'with_indexes' => true,
            'with_foreign_keys' => true,
        ]);

        $this->assertArrayHasKey('tables', $schema);
        $this->assertArrayHasKey('test_users', $schema['tables']);
        $this->assertArrayHasKey('columns', $schema['tables']['test_users']);
        $this->assertArrayHasKey('indexes', $schema['tables']['test_users']);
    }

    /** @test */
    public function it_can_generate_migration_file()
    {
        // Create a test table structure
        $tableStructure = [
            'name' => 'test_posts',
            'columns' => [
                [
                    'name' => 'id',
                    'type' => 'bigint',
                    'nullable' => false,
                    'auto_increment' => true,
                    'default' => null,
                ],
                [
                    'name' => 'title',
                    'type' => 'varchar',
                    'length' => 255,
                    'nullable' => false,
                    'default' => null,
                ],
                [
                    'name' => 'content',
                    'type' => 'text',
                    'nullable' => true,
                    'default' => null,
                ],
                [
                    'name' => 'created_at',
                    'type' => 'timestamp',
                    'nullable' => true,
                    'default' => null,
                ],
                [
                    'name' => 'updated_at',
                    'type' => 'timestamp',
                    'nullable' => true,
                    'default' => null,
                ],
            ],
            'primary_key' => ['columns' => ['id']],
            'indexes' => [],
            'foreign_keys' => [],
            'timestamps' => true,
            'soft_deletes' => false,
        ];

        $config = [
            'output_path' => $this->testOutputPath,
            'timestamp_prefix' => false,
            'force' => true,
            'with_indexes' => true,
            'with_foreign_keys' => true,
        ];

        $result = $this->generator->generateMigration(
            'testing',
            'test_posts',
            $tableStructure,
            ['tables' => ['test_posts' => $tableStructure]],
            $config
        );

        $this->assertTrue($result['generated']);
        $this->assertFileExists($result['path']);

        $content = file_get_contents($result['path']);
        $this->assertStringContainsString('Schema::create(\'test_posts\'', $content);
        $this->assertStringContainsString('$table->bigIncrements(\'id\')', $content);
        $this->assertStringContainsString('$table->string(\'title\', 255)', $content);
        $this->assertStringContainsString('$table->text(\'content\')->nullable()', $content);
    }

    /** @test */
    public function it_can_handle_foreign_keys()
    {
        $tableStructure = [
            'name' => 'test_comments',
            'columns' => [
                [
                    'name' => 'id',
                    'type' => 'bigint',
                    'nullable' => false,
                    'auto_increment' => true,
                ],
                [
                    'name' => 'post_id',
                    'type' => 'bigint',
                    'nullable' => false,
                ],
                [
                    'name' => 'content',
                    'type' => 'text',
                    'nullable' => false,
                ],
            ],
            'primary_key' => ['columns' => ['id']],
            'indexes' => [],
            'foreign_keys' => [
                [
                    'name' => 'comments_post_id_foreign',
                    'column' => 'post_id',
                    'foreign_table' => 'test_posts',
                    'foreign_column' => 'id',
                    'on_update' => 'CASCADE',
                    'on_delete' => 'CASCADE',
                ],
            ],
            'timestamps' => false,
            'soft_deletes' => false,
        ];

        $config = [
            'output_path' => $this->testOutputPath,
            'timestamp_prefix' => false,
            'force' => true,
            'with_foreign_keys' => true,
        ];

        $result = $this->generator->generateMigration(
            'testing',
            'test_comments',
            $tableStructure,
            ['tables' => ['test_comments' => $tableStructure]],
            $config
        );

        $content = file_get_contents($result['path']);
        $this->assertStringContainsString('->foreign(\'post_id\')->references(\'id\')->on(\'test_posts\')', $content);
        $this->assertStringContainsString('->onDelete(\'cascade\')', $content);
        $this->assertStringContainsString('->onUpdate(\'cascade\')', $content);
    }

    /** @test */
    public function it_can_handle_indexes()
    {
        $tableStructure = [
            'name' => 'test_indexed_table',
            'columns' => [
                [
                    'name' => 'id',
                    'type' => 'bigint',
                    'nullable' => false,
                    'auto_increment' => true,
                ],
                [
                    'name' => 'email',
                    'type' => 'varchar',
                    'length' => 255,
                    'nullable' => false,
                ],
                [
                    'name' => 'status',
                    'type' => 'varchar',
                    'length' => 50,
                    'nullable' => false,
                ],
            ],
            'primary_key' => ['columns' => ['id']],
            'indexes' => [
                [
                    'name' => 'unique_email',
                    'columns' => ['email'],
                    'unique' => true,
                ],
                [
                    'name' => 'idx_status',
                    'columns' => ['status'],
                    'unique' => false,
                ],
                [
                    'name' => 'idx_email_status',
                    'columns' => ['email', 'status'],
                    'unique' => false,
                ],
            ],
            'foreign_keys' => [],
            'timestamps' => false,
            'soft_deletes' => false,
        ];

        $config = [
            'output_path' => $this->testOutputPath,
            'timestamp_prefix' => false,
            'force' => true,
            'with_indexes' => true,
        ];

        $result = $this->generator->generateMigration(
            'testing',
            'test_indexed_table',
            $tableStructure,
            ['tables' => ['test_indexed_table' => $tableStructure]],
            $config
        );

        $content = file_get_contents($result['path']);
        $this->assertStringContainsString('$table->unique(\'email\')', $content);
        $this->assertStringContainsString('$table->index(\'status\')', $content);
        $this->assertStringContainsString('$table->index([\'email\', \'status\']', $content);
    }

    /** @test */
    public function it_can_order_tables_by_dependencies()
    {
        $schema = [
            'tables' => [
                'test_posts' => [
                    'foreign_keys' => [],
                ],
                'test_comments' => [
                    'foreign_keys' => [
                        ['foreign_table' => 'test_posts'],
                    ],
                ],
                'test_replies' => [
                    'foreign_keys' => [
                        ['foreign_table' => 'test_comments'],
                    ],
                ],
            ],
            'dependencies' => [
                'test_posts' => [],
                'test_comments' => ['test_posts'],
                'test_replies' => ['test_comments'],
            ],
        ];

        $ordered = $this->analyzer->orderTablesByDependencies($schema);

        $this->assertEquals(['test_posts', 'test_comments', 'test_replies'], $ordered);
    }

    /** @test */
    public function it_validates_migration_syntax()
    {
        // Create a valid migration file
        $validMigration = $this->testOutputPath . '/valid_migration.php';
        file_put_contents($validMigration, '<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(\'test\', function (Blueprint $table) {
            $table->id();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(\'test\');
    }
};');

        $errors = $this->generator->validateMigration($validMigration);
        $this->assertEmpty($errors);

        // Create an invalid migration file
        $invalidMigration = $this->testOutputPath . '/invalid_migration.php';
        file_put_contents($invalidMigration, '<?php
// Missing required methods and imports
class InvalidMigration {}
');

        $errors = $this->generator->validateMigration($invalidMigration);
        $this->assertNotEmpty($errors);
    }

    /** @test */
    public function it_can_handle_command_execution()
    {
        // This would test the actual command execution
        // In a real test, you'd set up a test database with known structure
        
        $this->artisan('autogen:migration', [
            '--connection' => 'testing',
            '--table' => 'migrations', // Use Laravel's migrations table as test
            '--output-path' => $this->testOutputPath,
            '--force' => true,
        ])->assertExitCode(0);
    }

    /** @test */
    public function it_handles_different_column_types()
    {
        $testCases = [
            ['type' => 'varchar', 'length' => 255, 'expected' => 'string(\'test\', 255)'],
            ['type' => 'text', 'expected' => 'text(\'test\')'],
            ['type' => 'int', 'expected' => 'integer(\'test\')'],
            ['type' => 'bigint', 'expected' => 'bigInteger(\'test\')'],
            ['type' => 'decimal', 'precision' => 8, 'scale' => 2, 'expected' => 'decimal(\'test\', 8, 2)'],
            ['type' => 'boolean', 'expected' => 'boolean(\'test\')'],
            ['type' => 'datetime', 'expected' => 'dateTime(\'test\')'],
            ['type' => 'json', 'expected' => 'json(\'test\')'],
        ];

        foreach ($testCases as $testCase) {
            $column = array_merge([
                'name' => 'test',
                'nullable' => false,
                'default' => null,
            ], $testCase);

            $tableStructure = ['primary_key' => null];
            $definition = $this->invokeMethod(
                $this->templateEngine,
                'generateColumnDefinition',
                [$column, $tableStructure]
            );

            $this->assertStringContainsString($testCase['expected'], $definition);
        }
    }

    /**
     * Call protected/private method of a class.
     */
    protected function invokeMethod(&$object, $methodName, array $parameters = [])
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }
}