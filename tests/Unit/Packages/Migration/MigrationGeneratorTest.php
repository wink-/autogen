<?php

declare(strict_types=1);

namespace AutoGen\Tests\Unit\Packages\Migration;

use AutoGen\Packages\Migration\MigrationGenerator;
use AutoGen\Packages\Migration\MigrationTemplateEngine;
use AutoGen\Tests\TestCase;
use AutoGen\Tests\Helpers\FileTestHelper;
use Illuminate\Support\Facades\File;
use Carbon\Carbon;

class MigrationGeneratorTest extends TestCase
{
    private MigrationGenerator $generator;
    private MigrationTemplateEngine $templateEngine;
    private string $tempPath;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->templateEngine = new MigrationTemplateEngine();
        $this->generator = new MigrationGenerator($this->templateEngine);
        $this->tempPath = $this->createTempDirectory('migrations');
    }

    /** @test */
    public function it_can_generate_basic_migration(): void
    {
        $config = $this->getBasicConfig();
        $tableStructure = $this->getBasicTableStructure();
        $schema = $this->getBasicSchema();

        $result = $this->generator->generateMigration(
            'testing',
            'users',
            $tableStructure,
            $schema,
            $config
        );

        $this->assertTrue($result['generated']);
        $this->assertStringContainsString('create_users_table.php', $result['file']);
        $this->assertFileExists($result['path']);
        
        $content = File::get($result['path']);
        $this->assertStringContainsString('class CreateUsersTable extends Migration', $content);
        $this->assertStringContainsString('Schema::create(\'users\'', $content);
        $this->assertStringContainsString('$table->id()', $content);
        $this->assertStringContainsString('$table->string(\'name\')', $content);
        $this->assertStringContainsString('$table->timestamps()', $content);
    }

    /** @test */
    public function it_generates_migration_with_timestamp_prefix(): void
    {
        $config = array_merge($this->getBasicConfig(), ['timestamp_prefix' => true]);
        
        $result = $this->generator->generateMigration(
            'testing',
            'posts',
            $this->getBasicTableStructure('posts'),
            $this->getBasicSchema(),
            $config
        );

        $this->assertTrue($result['generated']);
        $this->assertMatchesRegularExpression('/^\d{4}_\d{2}_\d{2}_\d{6}_create_posts_table\.php$/', $result['file']);
    }

    /** @test */
    public function it_skips_existing_migration_when_force_is_false(): void
    {
        $config = array_merge($this->getBasicConfig(), ['force' => false]);
        
        // Create existing migration file
        $existingFile = $this->tempPath . '/create_users_table.php';
        File::put($existingFile, '<?php // existing migration');

        $result = $this->generator->generateMigration(
            'testing',
            'users',
            $this->getBasicTableStructure(),
            $this->getBasicSchema(),
            $config
        );

        $this->assertFalse($result['generated']);
        $this->assertStringContainsString('already exists', $result['reason']);
    }

    /** @test */
    public function it_overwrites_existing_migration_when_force_is_true(): void
    {
        $config = array_merge($this->getBasicConfig(), ['force' => true]);
        
        // Create existing migration file
        $existingFile = $this->tempPath . '/create_users_table.php';
        File::put($existingFile, '<?php // existing migration');

        $result = $this->generator->generateMigration(
            'testing',
            'users',
            $this->getBasicTableStructure(),
            $this->getBasicSchema(),
            $config
        );

        $this->assertTrue($result['generated']);
        
        $content = File::get($result['path']);
        $this->assertStringNotContainsString('// existing migration', $content);
        $this->assertStringContainsString('class CreateUsersTable extends Migration', $content);
    }

    /** @test */
    public function it_can_generate_migration_with_foreign_keys(): void
    {
        $tableStructure = [
            'name' => 'posts',
            'columns' => [
                ['name' => 'id', 'type' => 'bigint', 'primary' => true, 'auto_increment' => true],
                ['name' => 'title', 'type' => 'varchar', 'length' => 255],
                ['name' => 'user_id', 'type' => 'bigint', 'nullable' => false],
            ],
            'foreign_keys' => [
                'user_id' => ['table' => 'users', 'column' => 'id', 'on_delete' => 'cascade'],
            ],
            'indexes' => [],
            'has_timestamps' => true,
        ];

        $result = $this->generator->generateMigration(
            'testing',
            'posts',
            $tableStructure,
            $this->getBasicSchema(),
            $this->getBasicConfig()
        );

        $content = File::get($result['path']);
        $this->assertStringContainsString('$table->foreignId(\'user_id\')', $content);
        $this->assertStringContainsString('->constrained()', $content);
        $this->assertStringContainsString('->onDelete(\'cascade\')', $content);
    }

    /** @test */
    public function it_can_generate_migration_with_indexes(): void
    {
        $tableStructure = [
            'name' => 'users',
            'columns' => [
                ['name' => 'id', 'type' => 'bigint', 'primary' => true, 'auto_increment' => true],
                ['name' => 'email', 'type' => 'varchar', 'length' => 255, 'unique' => true],
                ['name' => 'name', 'type' => 'varchar', 'length' => 255],
            ],
            'indexes' => [
                ['name' => 'users_email_unique', 'columns' => ['email'], 'unique' => true],
                ['name' => 'users_name_index', 'columns' => ['name'], 'unique' => false],
            ],
            'foreign_keys' => [],
            'has_timestamps' => true,
        ];

        $result = $this->generator->generateMigration(
            'testing',
            'users',
            $tableStructure,
            $this->getBasicSchema(),
            $this->getBasicConfig()
        );

        $content = File::get($result['path']);
        $this->assertStringContainsString('->unique()', $content);
        $this->assertStringContainsString('->index()', $content);
    }

    /** @test */
    public function it_can_generate_all_migrations_for_schema(): void
    {
        $schema = [
            'connection' => 'testing',
            'tables' => [
                'users' => $this->getBasicTableStructure('users'),
                'posts' => $this->getBasicTableStructure('posts'),
                'categories' => $this->getBasicTableStructure('categories'),
            ],
            'dependencies' => [
                'posts' => ['users'],
                'categories' => [],
                'users' => [],
            ],
        ];

        $results = $this->generator->generateAllMigrations($schema, $this->getBasicConfig());

        $this->assertCount(3, $results);
        foreach ($results as $result) {
            $this->assertTrue($result['generated']);
            $this->assertFileExists($result['path']);
        }
    }

    /** @test */
    public function it_can_generate_separate_foreign_key_migrations(): void
    {
        $schema = [
            'connection' => 'testing',
            'tables' => [
                'users' => $this->getBasicTableStructure('users'),
                'posts' => array_merge($this->getBasicTableStructure('posts'), [
                    'foreign_keys' => [
                        'user_id' => ['table' => 'users', 'column' => 'id'],
                    ],
                ]),
            ],
            'dependencies' => [
                'posts' => ['users'],
                'users' => ['posts'], // Circular dependency to trigger separation
            ],
        ];

        $config = array_merge($this->getBasicConfig(), ['with_foreign_keys' => true]);
        $results = $this->generator->generateAllMigrations($schema, $config);

        // Should have table migrations + foreign key migrations
        $this->assertGreaterThan(2, count($results));
        
        $foreignKeyMigration = collect($results)->firstWhere('type', 'foreign_keys');
        $this->assertNotNull($foreignKeyMigration);
        $this->assertTrue($foreignKeyMigration['generated']);
    }

    /** @test */
    public function it_can_generate_rollback_migration(): void
    {
        $result = $this->generator->generateRollbackMigration('users', $this->getBasicConfig());

        $this->assertTrue($result['generated']);
        $this->assertStringContainsString('drop_users_table.php', $result['file']);
        $this->assertEquals('rollback', $result['type']);
        
        $content = File::get($result['path']);
        $this->assertStringContainsString('class DropUsersTable extends Migration', $content);
        $this->assertStringContainsString('Schema::dropIfExists(\'users\')', $content);
    }

    /** @test */
    public function it_can_get_existing_migrations(): void
    {
        // Create some test migration files
        File::put($this->tempPath . '/2023_01_01_000000_create_users_table.php', '<?php // migration 1');
        File::put($this->tempPath . '/2023_01_02_000000_create_posts_table.php', '<?php // migration 2');
        File::put($this->tempPath . '/not_a_migration.txt', 'not a php file');

        $existing = $this->generator->getExistingMigrations($this->getBasicConfig());

        $this->assertCount(2, $existing);
        foreach ($existing as $migration) {
            $this->assertArrayHasKey('file', $migration);
            $this->assertArrayHasKey('path', $migration);
            $this->assertArrayHasKey('size', $migration);
            $this->assertArrayHasKey('modified', $migration);
            $this->assertStringEndsWith('.php', $migration['file']);
        }
    }

    /** @test */
    public function it_can_backup_existing_migrations(): void
    {
        // Create some test migration files
        File::put($this->tempPath . '/create_users_table.php', '<?php // users migration');
        File::put($this->tempPath . '/create_posts_table.php', '<?php // posts migration');

        $backupDir = $this->generator->backupExistingMigrations($this->getBasicConfig());

        $this->assertDirectoryExists($backupDir);
        $this->assertFileExists($backupDir . '/create_users_table.php');
        $this->assertFileExists($backupDir . '/create_posts_table.php');
        
        $this->assertEquals('<?php // users migration', File::get($backupDir . '/create_users_table.php'));
    }

    /** @test */
    public function it_can_cleanup_generated_migrations(): void
    {
        // Create test migration files
        File::put($this->tempPath . '/create_users_table.php', '<?php // autogen migration');
        File::put($this->tempPath . '/create_posts_table.php', '<?php // autogen migration');
        File::put($this->tempPath . '/manual_migration.php', '<?php // manual migration');

        $cleaned = $this->generator->cleanupMigrations($this->getBasicConfig());

        $this->assertEquals(2, $cleaned);
        $this->assertFileDoesNotExist($this->tempPath . '/create_users_table.php');
        $this->assertFileDoesNotExist($this->tempPath . '/create_posts_table.php');
        $this->assertFileExists($this->tempPath . '/manual_migration.php'); // Should not be deleted
    }

    /** @test */
    public function it_can_validate_migration_syntax(): void
    {
        // Create valid migration
        $validMigration = $this->tempPath . '/valid_migration.php';
        File::put($validMigration, '<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class ValidMigration extends Migration
{
    public function up() {
        // migration logic
    }
    
    public function down() {
        // rollback logic
    }
}');

        $errors = $this->generator->validateMigration($validMigration);
        $this->assertEmpty($errors);

        // Create invalid migration
        $invalidMigration = $this->tempPath . '/invalid_migration.php';
        File::put($invalidMigration, '<?php
class InvalidMigration
{
    // Missing required methods and imports
}');

        $errors = $this->generator->validateMigration($invalidMigration);
        $this->assertNotEmpty($errors);
        $this->assertContains('Missing up() method', $errors);
        $this->assertContains('Missing down() method', $errors);
        $this->assertContains('Missing Migration import', $errors);
        $this->assertContains('Missing Blueprint import', $errors);
    }

    /** @test */
    public function it_validates_non_existent_migration(): void
    {
        $errors = $this->generator->validateMigration($this->tempPath . '/non_existent.php');
        
        $this->assertNotEmpty($errors);
        $this->assertContains('Migration file does not exist', $errors);
    }

    /** @test */
    public function it_can_get_migration_statistics(): void
    {
        $results = [
            ['generated' => true, 'size' => 1000, 'type' => 'table'],
            ['generated' => true, 'size' => 500, 'type' => 'foreign_keys'],
            ['generated' => false, 'reason' => 'already exists'],
            ['generated' => true, 'size' => 800, 'type' => 'rollback'],
        ];

        $stats = $this->generator->getMigrationStats($results);

        $this->assertEquals(4, $stats['total']);
        $this->assertEquals(3, $stats['generated']);
        $this->assertEquals(1, $stats['skipped']);
        $this->assertEquals(0, $stats['errors']);
        $this->assertEquals(2300, $stats['total_size']);
        $this->assertEquals(1, $stats['types']['table']);
        $this->assertEquals(1, $stats['types']['foreign_keys']);
        $this->assertEquals(1, $stats['types']['rollback']);
    }

    /** @test */
    public function it_creates_output_directory_if_not_exists(): void
    {
        $nestedPath = $this->tempPath . '/deep/nested/path';
        $config = array_merge($this->getBasicConfig(), ['output_path' => $nestedPath]);

        $result = $this->generator->generateMigration(
            'testing',
            'users',
            $this->getBasicTableStructure(),
            $this->getBasicSchema(),
            $config
        );

        $this->assertTrue($result['generated']);
        $this->assertDirectoryExists($nestedPath);
        $this->assertFileExists($result['path']);
    }

    /** @test */
    public function it_handles_complex_column_types(): void
    {
        $tableStructure = [
            'name' => 'complex_table',
            'columns' => [
                ['name' => 'id', 'type' => 'bigint', 'primary' => true, 'auto_increment' => true],
                ['name' => 'decimal_field', 'type' => 'decimal', 'precision' => 10, 'scale' => 2],
                ['name' => 'enum_field', 'type' => 'enum', 'options' => ['active', 'inactive', 'pending']],
                ['name' => 'json_field', 'type' => 'json', 'nullable' => true],
                ['name' => 'text_field', 'type' => 'text'],
                ['name' => 'timestamp_field', 'type' => 'timestamp', 'default' => 'CURRENT_TIMESTAMP'],
            ],
            'foreign_keys' => [],
            'indexes' => [],
            'has_timestamps' => false,
        ];

        $result = $this->generator->generateMigration(
            'testing',
            'complex_table',
            $tableStructure,
            $this->getBasicSchema(),
            $this->getBasicConfig()
        );

        $content = File::get($result['path']);
        $this->assertStringContainsString('$table->decimal(\'decimal_field\', 10, 2)', $content);
        $this->assertStringContainsString('$table->enum(\'enum_field\', [\'active\', \'inactive\', \'pending\'])', $content);
        $this->assertStringContainsString('$table->json(\'json_field\')', $content);
        $this->assertStringContainsString('$table->text(\'text_field\')', $content);
        $this->assertStringContainsString('$table->timestamp(\'timestamp_field\')', $content);
    }

    private function getBasicConfig(): array
    {
        return [
            'output_path' => $this->tempPath,
            'timestamp_prefix' => false,
            'force' => false,
            'with_foreign_keys' => false,
        ];
    }

    private function getBasicTableStructure(string $tableName = 'users'): array
    {
        return [
            'name' => $tableName,
            'columns' => [
                ['name' => 'id', 'type' => 'bigint', 'primary' => true, 'auto_increment' => true],
                ['name' => 'name', 'type' => 'varchar', 'length' => 255],
                ['name' => 'email', 'type' => 'varchar', 'length' => 255, 'unique' => true],
            ],
            'foreign_keys' => [],
            'indexes' => [],
            'has_timestamps' => true,
        ];
    }

    private function getBasicSchema(): array
    {
        return [
            'connection' => 'testing',
            'tables' => [
                'users' => $this->getBasicTableStructure('users'),
            ],
            'dependencies' => [
                'users' => [],
            ],
        ];
    }

    protected function tearDown(): void
    {
        if (File::exists($this->tempPath)) {
            File::deleteDirectory($this->tempPath);
        }
        parent::tearDown();
    }
}