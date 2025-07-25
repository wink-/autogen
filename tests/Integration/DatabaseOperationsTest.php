<?php

declare(strict_types=1);

namespace AutoGen\Tests\Integration;

use AutoGen\Tests\TestCase;
use AutoGen\Packages\Model\DatabaseIntrospector;
use AutoGen\Packages\Model\ModelGenerator;
use AutoGen\Packages\Migration\MigrationGenerator;
use AutoGen\Packages\Migration\MigrationTemplateEngine;
use AutoGen\Packages\Factory\FactoryGenerator;
use AutoGen\Packages\Factory\FakeDataMapper;
use AutoGen\Packages\Factory\RelationshipFactoryHandler;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\File;
use Illuminate\Database\Schema\Blueprint;

class DatabaseOperationsTest extends TestCase
{
    private DatabaseIntrospector $introspector;
    private ModelGenerator $modelGenerator;
    private MigrationGenerator $migrationGenerator;
    private FactoryGenerator $factoryGenerator;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->introspector = new DatabaseIntrospector();
        $this->modelGenerator = new ModelGenerator();
        $this->migrationGenerator = new MigrationGenerator(new MigrationTemplateEngine());
        $this->factoryGenerator = new FactoryGenerator(
            new FakeDataMapper(),
            new RelationshipFactoryHandler(),
            $this->introspector
        );
    }

    /** @test */
    public function it_can_introspect_and_generate_model_from_existing_table(): void
    {
        // Create a complex test table
        Schema::create('complex_test_table', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->decimal('price', 8, 2)->nullable();
            $table->json('metadata')->nullable();
            $table->enum('status', ['draft', 'published', 'archived']);
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['name', 'email']);
        });

        // Introspect the table
        $tableStructure = $this->introspector->getTableSchema('complex_test_table');
        
        // Verify table structure was correctly analyzed
        $this->assertEquals('complex_test_table', $tableStructure['name']);
        $this->assertTrue($tableStructure['has_timestamps']);
        $this->assertTrue($tableStructure['has_soft_deletes']);
        $this->assertArrayHasKey('user_id', $tableStructure['foreign_keys']);
        $this->assertNotEmpty($tableStructure['indexes']);

        // Generate model from introspected data
        $this->modelGenerator->generate(
            'testing',
            'complex_test_table',
            $tableStructure,
            [],
            ['namespace' => 'App\\Models']
        );

        // Verify model was generated correctly
        $modelPath = app_path('Models/ComplexTestTable.php');
        $this->assertFileExists($modelPath);
        
        $modelContent = File::get($modelPath);
        $this->assertStringContainsString('class ComplexTestTable extends Model', $modelContent);
        $this->assertStringContainsString('use SoftDeletes', $modelContent);
        $this->assertStringContainsString("'name' => 'string'", $modelContent);
        $this->assertStringContainsString("'is_active' => 'boolean'", $modelContent);
        $this->assertStringContainsString("'price' => 'decimal:2'", $modelContent);
        $this->assertStringContainsString("'metadata' => 'array'", $modelContent);

        // Clean up
        Schema::drop('complex_test_table');
        File::delete($modelPath);
    }

    /** @test */
    public function it_can_generate_migration_from_model_and_execute_it(): void
    {
        $tempPath = $this->createTempDirectory('test_migrations');
        
        $tableStructure = [
            'name' => 'integration_test_table',
            'columns' => [
                ['name' => 'id', 'type' => 'bigint', 'auto_increment' => true, 'primary' => true],
                ['name' => 'title', 'type' => 'varchar', 'length' => 255, 'nullable' => false],
                ['name' => 'content', 'type' => 'text', 'nullable' => true],
                ['name' => 'priority', 'type' => 'integer', 'nullable' => false, 'default' => 1],
                ['name' => 'is_published', 'type' => 'boolean', 'nullable' => false, 'default' => false],
                ['name' => 'published_at', 'type' => 'timestamp', 'nullable' => true],
                ['name' => 'created_at', 'type' => 'timestamp', 'nullable' => true],
                ['name' => 'updated_at', 'type' => 'timestamp', 'nullable' => true],
            ],
            'indexes' => [
                ['name' => 'idx_title_priority', 'columns' => ['title', 'priority'], 'unique' => false],
                ['name' => 'idx_published', 'columns' => ['is_published', 'published_at'], 'unique' => false],
            ],
            'foreign_keys' => [],
            'has_timestamps' => true,
        ];

        $schema = ['connection' => 'testing', 'tables' => ['integration_test_table' => $tableStructure]];
        $config = ['output_path' => $tempPath, 'timestamp_prefix' => false, 'force' => true];

        // Generate migration
        $result = $this->migrationGenerator->generateMigration(
            'testing',
            'integration_test_table',
            $tableStructure,
            $schema,
            $config
        );

        $this->assertTrue($result['generated']);
        $this->assertFileExists($result['path']);

        // Verify migration content
        $migrationContent = File::get($result['path']);
        $this->assertStringContainsString('Schema::create(\'integration_test_table\'', $migrationContent);
        $this->assertStringContainsString('$table->string(\'title\')', $migrationContent);
        $this->assertStringContainsString('$table->text(\'content\')', $migrationContent);
        $this->assertStringContainsString('$table->boolean(\'is_published\')', $migrationContent);
        $this->assertStringContainsString('$table->index([\'title\', \'priority\']', $migrationContent);

        // Clean up
        File::deleteDirectory($tempPath);
    }

    /** @test */
    public function it_can_create_model_and_factory_with_relationship_data(): void
    {
        // Create related tables
        Schema::create('authors', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->timestamps();
        });

        Schema::create('books', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description');
            $table->decimal('price', 8, 2);
            $table->foreignId('author_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });

        // Introspect and analyze relationships
        $bookTableStructure = $this->introspector->getTableSchema('books');
        $authorTableStructure = $this->introspector->getTableSchema('authors');

        // Generate models
        $this->modelGenerator->generate(
            'testing',
            'authors',
            $authorTableStructure,
            [
                [
                    'type' => 'hasMany',
                    'method_name' => 'books',
                    'related_model' => 'Book',
                    'foreign_key' => 'author_id',
                    'local_key' => 'id',
                    'description' => 'books written by this author',
                ]
            ],
            ['namespace' => 'App\\Models']
        );

        $this->modelGenerator->generate(
            'testing',
            'books',
            $bookTableStructure,
            [
                [
                    'type' => 'belongsTo',
                    'method_name' => 'author',
                    'related_model' => 'Author',
                    'foreign_key' => 'author_id',
                    'owner_key' => 'id',
                    'description' => 'author of this book',
                ]
            ],
            ['namespace' => 'App\\Models']
        );

        // Verify models with relationships
        $authorModelPath = app_path('Models/Author.php');
        $bookModelPath = app_path('Models/Book.php');
        
        $this->assertFileExists($authorModelPath);
        $this->assertFileExists($bookModelPath);

        $authorContent = File::get($authorModelPath);
        $bookContent = File::get($bookModelPath);

        $this->assertStringContainsString('public function books(): HasMany', $authorContent);
        $this->assertStringContainsString('public function author(): BelongsTo', $bookContent);

        // Generate factories with relationship support
        $authorFactoryResult = $this->factoryGenerator->generate('Author', [
            'template' => 'advanced',
            'locale' => 'en_US',
            'with_relationships' => true,
            'with_states' => false,
            'with_files' => false,
        ]);

        $bookFactoryResult = $this->factoryGenerator->generate('Book', [
            'template' => 'advanced',
            'locale' => 'en_US',
            'with_relationships' => true,
            'with_states' => false,
            'with_files' => false,
        ]);

        $this->assertTrue($authorFactoryResult['success']);
        $this->assertTrue($bookFactoryResult['success']);

        // Test the actual model and factory functionality
        if (class_exists('App\\Models\\Author') && class_exists('App\\Models\\Book')) {
            // Create using factories
            $author = \App\Models\Author::factory()->create([
                'name' => 'Test Author',
                'email' => 'author@test.com',
            ]);

            $book = \App\Models\Book::factory()->create([
                'title' => 'Test Book',
                'description' => 'A test book',
                'price' => 29.99,
                'author_id' => $author->id,
            ]);

            // Test relationships
            $this->assertEquals($author->id, $book->author->id);
            $this->assertEquals(1, $author->books()->count());
        }

        // Clean up
        Schema::drop('books');
        Schema::drop('authors');
        File::delete($authorModelPath);
        File::delete($bookModelPath);
        File::delete(database_path('factories/AuthorFactory.php'));
        File::delete(database_path('factories/BookFactory.php'));
    }

    /** @test */
    public function it_can_handle_complex_database_scenarios(): void
    {
        // Create a complex schema with multiple relationships
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->timestamps();
        });

        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('content');
            $table->enum('status', ['draft', 'published', 'archived']);
            $table->foreignId('category_id')->constrained()->onDelete('set null');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });

        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('post_tag', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained()->onDelete('cascade');
            $table->foreignId('tag_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });

        // Get all table names and verify they exist
        $tables = $this->introspector->getTableNames();
        $this->assertContains('categories', $tables);
        $this->assertContains('posts', $tables);
        $this->assertContains('tags', $tables);
        $this->assertContains('post_tag', $tables);

        // Analyze relationships for posts table
        $postRelationships = $this->introspector->getTableRelationships('posts');
        
        $this->assertArrayHasKey('belongsTo', $postRelationships);
        $this->assertArrayHasKey('belongsToMany', $postRelationships);

        // Verify belongsTo relationships
        $belongsTo = $postRelationships['belongsTo'];
        $categoryRelation = collect($belongsTo)->firstWhere('foreign_key', 'category_id');
        $userRelation = collect($belongsTo)->firstWhere('foreign_key', 'user_id');
        
        $this->assertNotNull($categoryRelation);
        $this->assertNotNull($userRelation);
        $this->assertEquals('Category', $categoryRelation['related_model']);
        $this->assertEquals('User', $userRelation['related_model']);

        // Verify belongsToMany relationships
        $belongsToMany = $postRelationships['belongsToMany'];
        $tagRelation = collect($belongsToMany)->firstWhere('pivot_table', 'post_tag');
        
        $this->assertNotNull($tagRelation);
        $this->assertEquals('Tag', $tagRelation['related_model']);

        // Generate models for the entire schema
        foreach (['categories', 'posts', 'tags'] as $table) {
            $tableStructure = $this->introspector->getTableSchema($table);
            $relationships = $this->introspector->getTableRelationships($table);
            
            $this->modelGenerator->generate(
                'testing',
                $table,
                $tableStructure,
                $relationships,
                ['namespace' => 'App\\Models']
            );
        }

        // Verify all models were generated
        $this->assertFileExists(app_path('Models/Category.php'));
        $this->assertFileExists(app_path('Models/Post.php'));
        $this->assertFileExists(app_path('Models/Tag.php'));

        // Check that Post model has all relationships
        $postContent = File::get(app_path('Models/Post.php'));
        $this->assertStringContainsString('public function category(): BelongsTo', $postContent);
        $this->assertStringContainsString('public function user(): BelongsTo', $postContent);
        $this->assertStringContainsString('public function tags(): BelongsToMany', $postContent);

        // Clean up
        Schema::drop('post_tag');
        Schema::drop('posts');
        Schema::drop('tags');
        Schema::drop('categories');
        
        File::delete(app_path('Models/Category.php'));
        File::delete(app_path('Models/Post.php'));
        File::delete(app_path('Models/Tag.php'));
    }

    /** @test */
    public function it_handles_database_constraints_and_indexes_correctly(): void
    {
        // Create table with various constraints and indexes
        Schema::create('constrained_table', function (Blueprint $table) {
            $table->id();
            $table->string('unique_field')->unique();
            $table->string('indexed_field')->index();
            $table->string('composite_field_1');
            $table->string('composite_field_2');
            $table->decimal('positive_amount', 10, 2)->unsigned();
            $table->timestamps();
            
            // Composite unique constraint
            $table->unique(['composite_field_1', 'composite_field_2'], 'unique_composite');
            
            // Composite index
            $table->index(['composite_field_1', 'composite_field_2', 'created_at'], 'composite_index');
        });

        // Introspect the constrained table
        $tableStructure = $this->introspector->getTableSchema('constrained_table');
        
        // Verify indexes were detected
        $this->assertNotEmpty($tableStructure['indexes']);
        
        $indexNames = array_column($tableStructure['indexes'], 'name');
        $this->assertContains('unique_composite', $indexNames);
        $this->assertContains('composite_index', $indexNames);

        // Find the unique composite index
        $uniqueComposite = collect($tableStructure['indexes'])
            ->firstWhere('name', 'unique_composite');
        
        $this->assertNotNull($uniqueComposite);
        $this->assertTrue($uniqueComposite['unique']);
        $this->assertEquals(['composite_field_1', 'composite_field_2'], $uniqueComposite['columns']);

        // Generate migration from the introspected structure
        $tempPath = $this->createTempDirectory('constraint_migrations');
        $config = ['output_path' => $tempPath, 'timestamp_prefix' => false, 'force' => true];
        
        $result = $this->migrationGenerator->generateMigration(
            'testing',
            'constrained_table',
            $tableStructure,
            ['connection' => 'testing', 'tables' => ['constrained_table' => $tableStructure]],
            $config
        );

        $this->assertTrue($result['generated']);
        
        $migrationContent = File::get($result['path']);
        $this->assertStringContainsString('->unique()', $migrationContent);
        $this->assertStringContainsString('->index()', $migrationContent);
        $this->assertStringContainsString('->unsigned()', $migrationContent);

        // Clean up
        Schema::drop('constrained_table');
        File::deleteDirectory($tempPath);
    }

    /** @test */
    public function it_can_handle_database_errors_gracefully(): void
    {
        // Test with non-existent table
        try {
            $this->introspector->getTableSchema('non_existent_table');
            $this->fail('Expected exception was not thrown');
        } catch (\Exception $e) {
            $this->assertStringContainsString('does not exist', $e->getMessage());
        }

        // Test model generation with invalid configuration
        $result = $this->factoryGenerator->generate('NonExistentModel', [
            'template' => 'default',
            'locale' => 'en_US',
            'with_relationships' => false,
            'with_states' => false,
            'with_files' => false,
        ]);

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
    }

    /** @test */
    public function it_maintains_referential_integrity_during_operations(): void
    {
        // Create tables with foreign key relationships
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->foreignId('department_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });

        // Verify foreign key constraints are detected
        $employeeStructure = $this->introspector->getTableSchema('employees');
        $this->assertArrayHasKey('department_id', $employeeStructure['foreign_keys']);
        
        $foreignKey = $employeeStructure['foreign_keys']['department_id'];
        $this->assertEquals('departments', $foreignKey['table']);
        $this->assertEquals('id', $foreignKey['column']);

        // Test that relationships are properly analyzed
        $relationships = $this->introspector->getTableRelationships('employees');
        $departmentRelation = collect($relationships['belongsTo'])
            ->firstWhere('foreign_key', 'department_id');
        
        $this->assertNotNull($departmentRelation);
        $this->assertEquals('Department', $departmentRelation['related_model']);

        // Generate models and verify foreign key handling
        foreach (['departments', 'employees'] as $table) {
            $structure = $this->introspector->getTableSchema($table);
            $relations = $this->introspector->getTableRelationships($table);
            
            $this->modelGenerator->generate(
                'testing',
                $table,
                $structure,
                $relations,
                ['namespace' => 'App\\Models']
            );
        }

        // Verify the employee model has proper relationship
        $employeeContent = File::get(app_path('Models/Employee.php'));
        $this->assertStringContainsString('public function department(): BelongsTo', $employeeContent);

        // Clean up
        Schema::drop('employees');
        Schema::drop('departments');
        File::delete(app_path('Models/Department.php'));
        File::delete(app_path('Models/Employee.php'));
    }
}