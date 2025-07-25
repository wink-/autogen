<?php

declare(strict_types=1);

namespace AutoGen\Tests\Unit\Packages\Model;

use AutoGen\Packages\Model\RelationshipAnalyzer;
use AutoGen\Tests\TestCase;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RelationshipAnalyzerTest extends TestCase
{
    private RelationshipAnalyzer $analyzer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->analyzer = new RelationshipAnalyzer();
    }

    /** @test */
    public function it_can_detect_belongs_to_relationships(): void
    {
        $relationships = $this->analyzer->analyzeRelationships('posts');
        
        $this->assertArrayHasKey('belongsTo', $relationships);
        $belongsTo = $relationships['belongsTo'];
        
        $userRelation = collect($belongsTo)->firstWhere('foreign_key', 'user_id');
        $this->assertNotNull($userRelation);
        $this->assertEquals('User', $userRelation['related_model']);
        $this->assertEquals('user', $userRelation['method_name']);
        $this->assertEquals('user_id', $userRelation['foreign_key']);
        $this->assertEquals('id', $userRelation['owner_key']);
    }

    /** @test */
    public function it_can_detect_has_many_relationships(): void
    {
        $relationships = $this->analyzer->analyzeRelationships('users');
        
        $this->assertArrayHasKey('hasMany', $relationships);
        $hasMany = $relationships['hasMany'];
        
        $postsRelation = collect($hasMany)->firstWhere('related_table', 'posts');
        $this->assertNotNull($postsRelation);
        $this->assertEquals('Post', $postsRelation['related_model']);
        $this->assertEquals('posts', $postsRelation['method_name']);
        $this->assertEquals('user_id', $postsRelation['foreign_key']);
        $this->assertEquals('id', $postsRelation['local_key']);
    }

    /** @test */
    public function it_can_detect_belongs_to_many_relationships(): void
    {
        // Create pivot table for many-to-many relationship
        Schema::create('post_category', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained()->onDelete('cascade');
            $table->foreignId('category_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });

        $relationships = $this->analyzer->analyzeRelationships('posts');
        
        $this->assertArrayHasKey('belongsToMany', $relationships);
        $belongsToMany = $relationships['belongsToMany'];
        
        $categoriesRelation = collect($belongsToMany)->firstWhere('pivot_table', 'post_category');
        $this->assertNotNull($categoriesRelation);
        $this->assertEquals('Category', $categoriesRelation['related_model']);
        $this->assertEquals('categories', $categoriesRelation['method_name']);
        $this->assertEquals('post_category', $categoriesRelation['pivot_table']);
        $this->assertEquals('post_id', $categoriesRelation['foreign_pivot_key']);
        $this->assertEquals('category_id', $categoriesRelation['related_pivot_key']);
        
        Schema::drop('post_category');
    }

    /** @test */
    public function it_can_detect_has_one_relationships(): void
    {
        // Create a table with a one-to-one relationship
        Schema::create('user_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->onDelete('cascade');
            $table->text('bio')->nullable();
            $table->string('website')->nullable();
            $table->timestamps();
        });

        $relationships = $this->analyzer->analyzeRelationships('users');
        
        $this->assertArrayHasKey('hasOne', $relationships);
        $hasOne = $relationships['hasOne'];
        
        $profileRelation = collect($hasOne)->firstWhere('related_table', 'user_profiles');
        $this->assertNotNull($profileRelation);
        $this->assertEquals('UserProfile', $profileRelation['related_model']);
        $this->assertEquals('userProfile', $profileRelation['method_name']);
        $this->assertEquals('user_id', $profileRelation['foreign_key']);
        $this->assertEquals('id', $profileRelation['local_key']);
        
        Schema::drop('user_profiles');
    }

    /** @test */
    public function it_can_detect_self_referencing_relationships(): void
    {
        // Add parent_id to comments for self-referencing relationship
        Schema::table('comments', function (Blueprint $table) {
            // parent_id already exists in the test schema
        });

        $relationships = $this->analyzer->analyzeRelationships('comments');
        
        $belongsTo = $relationships['belongsTo'];
        $parentRelation = collect($belongsTo)->firstWhere('foreign_key', 'parent_id');
        $this->assertNotNull($parentRelation);
        $this->assertEquals('Comment', $parentRelation['related_model']);
        $this->assertEquals('parent', $parentRelation['method_name']);
        
        $hasMany = $relationships['hasMany'];
        $childrenRelation = collect($hasMany)->firstWhere('foreign_key', 'parent_id');
        $this->assertNotNull($childrenRelation);
        $this->assertEquals('Comment', $childrenRelation['related_model']);
        $this->assertEquals('children', $childrenRelation['method_name']);
    }

    /** @test */
    public function it_generates_correct_method_names(): void
    {
        $testCases = [
            ['table' => 'user_profiles', 'expected' => 'userProfile'],
            ['table' => 'blog_posts', 'expected' => 'blogPost'],
            ['table' => 'posts', 'expected' => 'post'],
            ['table' => 'categories', 'expected' => 'category'],
            ['table' => 'order_items', 'expected' => 'orderItem'],
        ];

        foreach ($testCases as $case) {
            $methodName = $this->analyzer->getMethodName($case['table'], false);
            $this->assertEquals($case['expected'], $methodName);
        }
    }

    /** @test */
    public function it_generates_correct_plural_method_names(): void
    {
        $testCases = [
            ['table' => 'posts', 'expected' => 'posts'],
            ['table' => 'categories', 'expected' => 'categories'],
            ['table' => 'comments', 'expected' => 'comments'],
            ['table' => 'user_profiles', 'expected' => 'userProfiles'],
            ['table' => 'blog_posts', 'expected' => 'blogPosts'],
        ];

        foreach ($testCases as $case) {
            $methodName = $this->analyzer->getMethodName($case['table'], true);
            $this->assertEquals($case['expected'], $methodName);
        }
    }

    /** @test */
    public function it_can_detect_polymorphic_relationships(): void
    {
        // Create polymorphic relationship
        Schema::create('images', function (Blueprint $table) {
            $table->id();
            $table->string('url');
            $table->morphs('imageable');
            $table->timestamps();
        });

        $relationships = $this->analyzer->analyzeRelationships('images');
        
        $this->assertArrayHasKey('morphTo', $relationships);
        $morphTo = $relationships['morphTo'];
        
        $imageableRelation = collect($morphTo)->firstWhere('name', 'imageable');
        $this->assertNotNull($imageableRelation);
        $this->assertEquals('imageable', $imageableRelation['method_name']);
        $this->assertEquals('imageable_type', $imageableRelation['type_column']);
        $this->assertEquals('imageable_id', $imageableRelation['id_column']);
        
        Schema::drop('images');
    }

    /** @test */
    public function it_can_detect_reverse_polymorphic_relationships(): void
    {
        // Create polymorphic relationship
        Schema::create('images', function (Blueprint $table) {
            $table->id();
            $table->string('url');
            $table->morphs('imageable');
            $table->timestamps();
        });

        $relationships = $this->analyzer->analyzeRelationships('posts');
        
        $this->assertArrayHasKey('morphMany', $relationships);
        $morphMany = $relationships['morphMany'];
        
        $imagesRelation = collect($morphMany)->firstWhere('related_table', 'images');
        $this->assertNotNull($imagesRelation);
        $this->assertEquals('Image', $imagesRelation['related_model']);
        $this->assertEquals('images', $imagesRelation['method_name']);
        $this->assertEquals('imageable', $imagesRelation['name']);
        
        Schema::drop('images');
    }

    /** @test */
    public function it_handles_compound_foreign_keys(): void
    {
        // Create a table with compound foreign key
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->string('order_number');
            $table->integer('item_sequence');
            $table->string('product_sku');
            $table->integer('quantity');
            $table->timestamps();
            
            $table->foreign(['order_number', 'item_sequence'])
                  ->references(['number', 'sequence'])
                  ->on('orders');
        });

        // For compound keys, we expect the analyzer to handle them appropriately
        $relationships = $this->analyzer->analyzeRelationships('order_items');
        
        $this->assertIsArray($relationships);
        $this->assertArrayHasKey('belongsTo', $relationships);
        
        Schema::drop('order_items');
    }

    /** @test */
    public function it_excludes_certain_tables_from_relationships(): void
    {
        $excludedTables = ['migrations', 'password_resets', 'failed_jobs'];
        
        $relationships = $this->analyzer->analyzeRelationships('users', $excludedTables);
        
        $belongsTo = $relationships['belongsTo'];
        $hasMany = $relationships['hasMany'];
        
        // Check that no relationships point to excluded tables
        foreach ($belongsTo as $relation) {
            $this->assertNotContains($relation['related_table'], $excludedTables);
        }
        
        foreach ($hasMany as $relation) {
            $this->assertNotContains($relation['related_table'], $excludedTables);
        }
    }

    /** @test */
    public function it_detects_cascade_delete_constraints(): void
    {
        $relationships = $this->analyzer->analyzeRelationships('posts');
        
        $belongsTo = $relationships['belongsTo'];
        $userRelation = collect($belongsTo)->firstWhere('foreign_key', 'user_id');
        
        $this->assertNotNull($userRelation);
        $this->assertEquals('cascade', $userRelation['on_delete'] ?? null);
    }

    /** @test */
    public function it_handles_tables_with_no_relationships(): void
    {
        // Create a standalone table
        Schema::create('standalone_table', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description');
            $table->timestamps();
        });

        $relationships = $this->analyzer->analyzeRelationships('standalone_table');
        
        $this->assertIsArray($relationships);
        $this->assertEmpty($relationships['belongsTo']);
        $this->assertEmpty($relationships['hasMany']);
        $this->assertEmpty($relationships['belongsToMany']);
        $this->assertEmpty($relationships['hasOne']);
        
        Schema::drop('standalone_table');
    }

    /** @test */
    public function it_can_infer_pivot_table_names(): void
    {
        $pivotTable = $this->analyzer->inferPivotTableName('posts', 'tags');
        $this->assertEquals('post_tag', $pivotTable);
        
        $pivotTable = $this->analyzer->inferPivotTableName('categories', 'posts');
        $this->assertEquals('category_post', $pivotTable);
        
        $pivotTable = $this->analyzer->inferPivotTableName('users', 'roles');
        $this->assertEquals('role_user', $pivotTable);
    }

    /** @test */
    public function it_generates_correct_model_names(): void
    {
        $testCases = [
            'users' => 'User',
            'blog_posts' => 'BlogPost',
            'user_profiles' => 'UserProfile',
            'categories' => 'Category',
            'order_items' => 'OrderItem',
            'product_categories' => 'ProductCategory',
        ];

        foreach ($testCases as $table => $expectedModel) {
            $modelName = $this->analyzer->getModelName($table);
            $this->assertEquals($expectedModel, $modelName);
        }
    }

    /** @test */
    public function it_can_detect_nullable_foreign_keys(): void
    {
        // Create a table with nullable foreign key
        Schema::create('optional_relations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('category_id')->nullable()->constrained()->onDelete('set null');
            $table->timestamps();
        });

        $relationships = $this->analyzer->analyzeRelationships('optional_relations');
        
        $belongsTo = $relationships['belongsTo'];
        $categoryRelation = collect($belongsTo)->firstWhere('foreign_key', 'category_id');
        
        $this->assertNotNull($categoryRelation);
        $this->assertTrue($categoryRelation['nullable'] ?? false);
        $this->assertEquals('set null', $categoryRelation['on_delete'] ?? null);
        
        Schema::drop('optional_relations');
    }
}