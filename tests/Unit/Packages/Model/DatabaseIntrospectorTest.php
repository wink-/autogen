<?php

declare(strict_types=1);

namespace AutoGen\Tests\Unit\Packages\Model;

use AutoGen\Packages\Model\DatabaseIntrospector;
use AutoGen\Tests\TestCase;
use AutoGen\Tests\Helpers\DatabaseTestHelper;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DatabaseIntrospectorTest extends TestCase
{
    private DatabaseIntrospector $introspector;

    protected function setUp(): void
    {
        parent::setUp();
        $this->introspector = new DatabaseIntrospector();
    }

    /** @test */
    public function it_can_get_all_table_names(): void
    {
        $tables = $this->introspector->getTableNames();
        
        $this->assertIsArray($tables);
        $this->assertContains('users', $tables);
        $this->assertContains('posts', $tables);
        $this->assertContains('categories', $tables);
    }

    /** @test */
    public function it_can_exclude_certain_tables(): void
    {
        $tables = $this->introspector->getTableNames(['migrations', 'password_resets']);
        
        $this->assertIsArray($tables);
        $this->assertNotContains('migrations', $tables);
        $this->assertNotContains('password_resets', $tables);
    }

    /** @test */
    public function it_can_get_table_schema(): void
    {
        $schema = $this->introspector->getTableSchema('users');
        
        $this->assertIsArray($schema);
        $this->assertArrayHasKey('name', $schema);
        $this->assertArrayHasKey('columns', $schema);
        $this->assertArrayHasKey('primary_key', $schema);
        $this->assertArrayHasKey('foreign_keys', $schema);
        $this->assertArrayHasKey('indexes', $schema);
        $this->assertArrayHasKey('has_timestamps', $schema);
        $this->assertArrayHasKey('has_soft_deletes', $schema);
        
        $this->assertEquals('users', $schema['name']);
        $this->assertTrue($schema['has_timestamps']);
        $this->assertFalse($schema['has_soft_deletes']);
    }

    /** @test */
    public function it_can_detect_column_information(): void
    {
        $schema = $this->introspector->getTableSchema('users');
        $columns = $schema['columns'];
        
        // Find the 'name' column
        $nameColumn = collect($columns)->firstWhere('name', 'name');
        $this->assertNotNull($nameColumn);
        $this->assertEquals('string', $nameColumn['type']);
        $this->assertFalse($nameColumn['nullable']);
        
        // Find the 'email' column
        $emailColumn = collect($columns)->firstWhere('name', 'email');
        $this->assertNotNull($emailColumn);
        $this->assertEquals('string', $emailColumn['type']);
        $this->assertFalse($emailColumn['nullable']);
        
        // Find the 'id' column
        $idColumn = collect($columns)->firstWhere('name', 'id');
        $this->assertNotNull($idColumn);
        $this->assertEquals('integer', $idColumn['type']);
        $this->assertFalse($idColumn['nullable']);
        $this->assertTrue($idColumn['auto_increment'] ?? false);
    }

    /** @test */
    public function it_can_detect_primary_key(): void
    {
        $schema = $this->introspector->getTableSchema('users');
        $primaryKey = $schema['primary_key'];
        
        $this->assertIsArray($primaryKey);
        $this->assertArrayHasKey('name', $primaryKey);
        $this->assertArrayHasKey('columns', $primaryKey);
        $this->assertEquals(['id'], $primaryKey['columns']);
    }

    /** @test */
    public function it_can_detect_foreign_keys(): void
    {
        $schema = $this->introspector->getTableSchema('posts');
        $foreignKeys = $schema['foreign_keys'];
        
        $this->assertIsArray($foreignKeys);
        $this->assertArrayHasKey('user_id', $foreignKeys);
        
        $userForeignKey = $foreignKeys['user_id'];
        $this->assertEquals('users', $userForeignKey['table']);
        $this->assertEquals('id', $userForeignKey['column']);
    }

    /** @test */
    public function it_can_detect_indexes(): void
    {
        // Create a test table with indexes
        Schema::create('test_indexed_table', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('email');
            $table->timestamps();
            
            $table->index('name');
            $table->index(['name', 'email'], 'name_email_index');
        });

        $schema = $this->introspector->getTableSchema('test_indexed_table');
        $indexes = $schema['indexes'];
        
        $this->assertIsArray($indexes);
        $this->assertNotEmpty($indexes);
        
        // Check for unique index on slug
        $uniqueIndex = collect($indexes)->first(function ($index) {
            return $index['unique'] && in_array('slug', $index['columns']);
        });
        $this->assertNotNull($uniqueIndex);
        
        Schema::drop('test_indexed_table');
    }

    /** @test */
    public function it_can_detect_soft_deletes(): void
    {
        // Create a table with soft deletes
        Schema::create('soft_delete_table', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
            $table->softDeletes();
        });

        $schema = $this->introspector->getTableSchema('soft_delete_table');
        
        $this->assertTrue($schema['has_soft_deletes']);
        
        // Check that deleted_at column exists
        $deletedAtColumn = collect($schema['columns'])->firstWhere('name', 'deleted_at');
        $this->assertNotNull($deletedAtColumn);
        $this->assertTrue($deletedAtColumn['nullable']);
        
        Schema::drop('soft_delete_table');
    }

    /** @test */
    public function it_can_detect_timestamps(): void
    {
        // Create a table without timestamps
        Schema::create('no_timestamps_table', function (Blueprint $table) {
            $table->id();
            $table->string('name');
        });

        $schema = $this->introspector->getTableSchema('no_timestamps_table');
        $this->assertFalse($schema['has_timestamps']);
        
        Schema::drop('no_timestamps_table');
    }

    /** @test */
    public function it_can_handle_different_column_types(): void
    {
        // Create a table with various column types
        Schema::create('various_types_table', function (Blueprint $table) {
            $table->id();
            $table->string('varchar_field', 100);
            $table->text('text_field');
            $table->integer('integer_field');
            $table->bigInteger('bigint_field');
            $table->decimal('decimal_field', 8, 2);
            $table->float('float_field');
            $table->double('double_field');
            $table->boolean('boolean_field');
            $table->date('date_field');
            $table->time('time_field');
            $table->dateTime('datetime_field');
            $table->timestamp('timestamp_field');
            $table->json('json_field');
            $table->enum('enum_field', ['option1', 'option2', 'option3']);
            $table->timestamps();
        });

        $schema = $this->introspector->getTableSchema('various_types_table');
        $columns = collect($schema['columns'])->keyBy('name');
        
        $this->assertEquals('string', $columns['varchar_field']['type']);
        $this->assertEquals('text', $columns['text_field']['type']);
        $this->assertEquals('integer', $columns['integer_field']['type']);
        $this->assertEquals('integer', $columns['bigint_field']['type']);
        $this->assertEquals('decimal', $columns['decimal_field']['type']);
        $this->assertEquals('float', $columns['float_field']['type']);
        $this->assertEquals('float', $columns['double_field']['type']);
        $this->assertEquals('boolean', $columns['boolean_field']['type']);
        $this->assertEquals('date', $columns['date_field']['type']);
        $this->assertEquals('time', $columns['time_field']['type']);
        $this->assertEquals('datetime', $columns['datetime_field']['type']);
        $this->assertEquals('datetime', $columns['timestamp_field']['type']);
        $this->assertEquals('json', $columns['json_field']['type']);
        
        Schema::drop('various_types_table');
    }

    /** @test */
    public function it_can_get_table_relationships(): void
    {
        $relationships = $this->introspector->getTableRelationships('posts');
        
        $this->assertIsArray($relationships);
        $this->assertArrayHasKey('belongsTo', $relationships);
        $this->assertArrayHasKey('hasMany', $relationships);
        $this->assertArrayHasKey('belongsToMany', $relationships);
        
        // Check belongsTo relationship to users
        $belongsTo = $relationships['belongsTo'];
        $userRelation = collect($belongsTo)->firstWhere('foreign_key', 'user_id');
        $this->assertNotNull($userRelation);
        $this->assertEquals('User', $userRelation['related_model']);
        $this->assertEquals('users', $userRelation['related_table']);
    }

    /** @test */
    public function it_can_handle_composite_primary_keys(): void
    {
        // Create a table with composite primary key
        Schema::create('composite_key_table', function (Blueprint $table) {
            $table->integer('first_id');
            $table->integer('second_id');
            $table->string('data');
            $table->timestamps();
            
            $table->primary(['first_id', 'second_id']);
        });

        $schema = $this->introspector->getTableSchema('composite_key_table');
        $primaryKey = $schema['primary_key'];
        
        $this->assertCount(2, $primaryKey['columns']);
        $this->assertContains('first_id', $primaryKey['columns']);
        $this->assertContains('second_id', $primaryKey['columns']);
        
        Schema::drop('composite_key_table');
    }

    /** @test */
    public function it_handles_non_existent_table_gracefully(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Table non_existent_table does not exist');
        
        $this->introspector->getTableSchema('non_existent_table');
    }

    /** @test */
    public function it_can_get_column_default_values(): void
    {
        Schema::create('default_values_table', function (Blueprint $table) {
            $table->id();
            $table->string('name')->default('Unknown');
            $table->boolean('is_active')->default(true);
            $table->integer('count')->default(0);
            $table->timestamp('published_at')->nullable();
        });

        $schema = $this->introspector->getTableSchema('default_values_table');
        $columns = collect($schema['columns'])->keyBy('name');
        
        $this->assertEquals('Unknown', $columns['name']['default'] ?? null);
        $this->assertTrue($columns['is_active']['default'] ?? false);
        $this->assertEquals(0, $columns['count']['default'] ?? null);
        
        Schema::drop('default_values_table');
    }

    /** @test */
    public function it_can_detect_nullable_columns(): void
    {
        Schema::create('nullable_test_table', function (Blueprint $table) {
            $table->id();
            $table->string('required_field');
            $table->string('optional_field')->nullable();
            $table->text('nullable_text')->nullable();
            $table->integer('nullable_int')->nullable();
        });

        $schema = $this->introspector->getTableSchema('nullable_test_table');
        $columns = collect($schema['columns'])->keyBy('name');
        
        $this->assertFalse($columns['required_field']['nullable']);
        $this->assertTrue($columns['optional_field']['nullable']);
        $this->assertTrue($columns['nullable_text']['nullable']);
        $this->assertTrue($columns['nullable_int']['nullable']);
        
        Schema::drop('nullable_test_table');
    }

    /** @test */
    public function it_can_handle_many_to_many_relationships(): void
    {
        // Create pivot table for many-to-many relationship
        Schema::create('post_tag', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained()->onDelete('cascade');
            $table->foreignId('tag_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });

        $relationships = $this->introspector->getTableRelationships('posts');
        $belongsToMany = $relationships['belongsToMany'];
        
        $tagRelation = collect($belongsToMany)->firstWhere('pivot_table', 'post_tag');
        $this->assertNotNull($tagRelation);
        $this->assertEquals('Tag', $tagRelation['related_model']);
        $this->assertEquals('post_id', $tagRelation['foreign_pivot_key']);
        $this->assertEquals('tag_id', $tagRelation['related_pivot_key']);
        
        Schema::drop('post_tag');
    }
}