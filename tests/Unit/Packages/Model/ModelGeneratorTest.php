<?php

declare(strict_types=1);

namespace AutoGen\Tests\Unit\Packages\Model;

use AutoGen\Packages\Model\ModelGenerator;
use AutoGen\Tests\TestCase;
use AutoGen\Tests\Helpers\DatabaseTestHelper;
use AutoGen\Tests\Helpers\FileTestHelper;
use Illuminate\Support\Facades\File;

class ModelGeneratorTest extends TestCase
{
    private ModelGenerator $generator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->generator = new ModelGenerator();
    }

    /** @test */
    public function it_can_generate_basic_model(): void
    {
        $tableStructure = $this->getBasicTableStructure();
        $config = $this->getBasicConfig();

        $this->generator->generate(
            'testing',
            'users', 
            $tableStructure,
            [],
            $config
        );

        $modelPath = $this->getModelPath('User');
        $this->assertFileGenerated($modelPath, [
            'namespace App\\Models',
            'class User extends Model',
            'use HasFactory',
            'protected $connection = self::CONNECTION',
            'protected $table = \'users\'',
        ]);
    }

    /** @test */
    public function it_generates_correct_model_name_from_table(): void
    {
        $testCases = [
            'users' => 'User',
            'blog_posts' => 'BlogPost',
            'user_profiles' => 'UserProfile',
            'categories' => 'Category',
            'products_categories' => 'ProductsCategory',
        ];

        foreach ($testCases as $table => $expectedModel) {
            $actualModel = $this->generator->getModelName($table);
            $this->assertEquals($expectedModel, $actualModel);
        }
    }

    /** @test */
    public function it_generates_correct_namespace(): void
    {
        $testCases = [
            ['namespace' => 'App\\Models'] => 'App\\Models',
            ['namespace' => 'App\\Models', 'dir' => 'Admin'] => 'App\\Models',
            ['dir' => 'Admin'] => 'App\\Models\\Admin',
            ['dir' => 'Admin/Users'] => 'App\\Models\\Admin\\Users',
            [] => 'App\\Models',
        ];

        foreach ($testCases as $config => $expected) {
            $actual = $this->generator->getNamespace($config);
            $this->assertEquals($expected, $actual);
        }
    }

    /** @test */
    public function it_generates_fillable_array_correctly(): void
    {
        $tableStructure = [
            'name' => 'users',
            'columns' => [
                ['name' => 'id', 'type' => 'bigint', 'auto_increment' => true],
                ['name' => 'name', 'type' => 'varchar', 'nullable' => false],
                ['name' => 'email', 'type' => 'varchar', 'nullable' => false],
                ['name' => 'password', 'type' => 'varchar', 'nullable' => false],
                ['name' => 'created_at', 'type' => 'timestamp', 'nullable' => true],
                ['name' => 'updated_at', 'type' => 'timestamp', 'nullable' => true],
            ],
            'primary_key' => ['columns' => ['id']],
            'has_timestamps' => true,
            'has_soft_deletes' => false,
        ];

        $this->generator->generate(
            'testing',
            'users',
            $tableStructure,
            [],
            $this->getBasicConfig()
        );

        $modelPath = $this->getModelPath('User');
        $content = File::get($modelPath);
        
        $this->assertStringContainsString("'name'", $content);
        $this->assertStringContainsString("'email'", $content);
        $this->assertStringContainsString("'password'", $content);
        $this->assertStringNotContainsString("'id'", $content);
        $this->assertStringNotContainsString("'created_at'", $content);
        $this->assertStringNotContainsString("'updated_at'", $content);
    }

    /** @test */
    public function it_generates_hidden_array_for_sensitive_fields(): void
    {
        $tableStructure = [
            'name' => 'users',
            'columns' => [
                ['name' => 'id', 'type' => 'bigint'],
                ['name' => 'name', 'type' => 'varchar'],
                ['name' => 'email', 'type' => 'varchar'],
                ['name' => 'password', 'type' => 'varchar'],
                ['name' => 'remember_token', 'type' => 'varchar'],
                ['name' => 'api_token', 'type' => 'varchar'],
            ],
            'primary_key' => ['columns' => ['id']],
            'has_timestamps' => true,
            'has_soft_deletes' => false,
        ];

        $this->generator->generate(
            'testing',
            'users',
            $tableStructure,
            [],
            $this->getBasicConfig()
        );

        $modelPath = $this->getModelPath('User');
        $content = File::get($modelPath);
        
        $this->assertStringContainsString("'password'", $content);
        $this->assertStringContainsString("'remember_token'", $content);
        $this->assertStringContainsString("'api_token'", $content);
    }

    /** @test */
    public function it_generates_correct_casts(): void
    {
        $tableStructure = [
            'name' => 'sample_table',
            'columns' => [
                ['name' => 'id', 'type' => 'bigint'],
                ['name' => 'is_active', 'type' => 'tinyint', 'length' => 1, 'full_type' => 'tinyint(1)'],
                ['name' => 'price', 'type' => 'decimal', 'scale' => 2],
                ['name' => 'metadata', 'type' => 'json'],
                ['name' => 'published_at', 'type' => 'timestamp'],
                ['name' => 'count', 'type' => 'integer'],
            ],
            'primary_key' => ['columns' => ['id']],
            'has_timestamps' => true,
            'has_soft_deletes' => false,
        ];

        $this->generator->generate(
            'testing',
            'sample_table',
            $tableStructure,
            [],
            $this->getBasicConfig()
        );

        $modelPath = $this->getModelPath('SampleTable');
        $content = File::get($modelPath);
        
        $this->assertStringContainsString("'is_active' => 'boolean'", $content);
        $this->assertStringContainsString("'price' => 'decimal:2'", $content);
        $this->assertStringContainsString("'metadata' => 'array'", $content);
        $this->assertStringContainsString("'published_at' => 'datetime'", $content);
        $this->assertStringContainsString("'count' => 'integer'", $content);
    }

    /** @test */
    public function it_generates_soft_deletes_trait_when_applicable(): void
    {
        $tableStructure = $this->getBasicTableStructure();
        $tableStructure['has_soft_deletes'] = true;
        $tableStructure['columns'][] = ['name' => 'deleted_at', 'type' => 'timestamp', 'nullable' => true];

        $this->generator->generate(
            'testing',
            'users',
            $tableStructure,
            [],
            $this->getBasicConfig()
        );

        $modelPath = $this->getModelPath('User');
        $content = File::get($modelPath);
        
        $this->assertStringContainsString('use Illuminate\\Database\\Eloquent\\SoftDeletes', $content);
        $this->assertStringContainsString('use HasFactory, SoftDeletes', $content);
    }

    /** @test */
    public function it_generates_relationships(): void
    {
        $relationships = [
            [
                'type' => 'belongsTo',
                'method_name' => 'user',
                'related_model' => 'User',
                'foreign_key' => 'user_id',
                'owner_key' => 'id',
                'description' => 'user that owns this post',
            ],
            [
                'type' => 'hasMany',
                'method_name' => 'comments',
                'related_model' => 'Comment',
                'foreign_key' => 'post_id',
                'local_key' => 'id',
                'description' => 'comments for this post',
            ],
        ];

        $this->generator->generate(
            'testing',
            'posts',
            $this->getBasicTableStructure('posts'),
            $relationships,
            $this->getBasicConfig()
        );

        $modelPath = $this->getModelPath('Post');
        $content = File::get($modelPath);
        
        // Check belongsTo relationship
        $this->assertStringContainsString('public function user(): BelongsTo', $content);
        $this->assertStringContainsString("return \$this->belongsTo(User::class, 'user_id', 'id')", $content);
        
        // Check hasMany relationship
        $this->assertStringContainsString('public function comments(): HasMany', $content);
        $this->assertStringContainsString("return \$this->hasMany(Comment::class, 'post_id', 'id')", $content);
        
        // Check relationship imports
        $this->assertStringContainsString('use Illuminate\\Database\\Eloquent\\Relations\\BelongsTo', $content);
        $this->assertStringContainsString('use Illuminate\\Database\\Eloquent\\Relations\\HasMany', $content);
    }

    /** @test */
    public function it_generates_belongstomany_relationships(): void
    {
        $relationships = [
            [
                'type' => 'belongsToMany',
                'method_name' => 'tags',
                'related_model' => 'Tag',
                'pivot_table' => 'post_tags',
                'foreign_pivot_key' => 'post_id',
                'related_pivot_key' => 'tag_id',
                'description' => 'tags associated with this post',
            ],
        ];

        $this->generator->generate(
            'testing',
            'posts',
            $this->getBasicTableStructure('posts'),
            $relationships,
            $this->getBasicConfig()
        );

        $modelPath = $this->getModelPath('Post');
        $content = File::get($modelPath);
        
        $this->assertStringContainsString('public function tags(): BelongsToMany', $content);
        $this->assertStringContainsString("return \$this->belongsToMany(Tag::class, 'post_tags', 'post_id', 'tag_id')", $content);
        $this->assertStringContainsString('use Illuminate\\Database\\Eloquent\\Relations\\BelongsToMany', $content);
    }

    /** @test */
    public function it_generates_scopes_when_configured(): void
    {
        $config = array_merge($this->getBasicConfig(), ['with_scopes' => true]);
        $tableStructure = $this->getBasicTableStructure();
        $tableStructure['columns'][] = ['name' => 'status', 'type' => 'varchar'];
        $tableStructure['columns'][] = ['name' => 'is_active', 'type' => 'boolean'];
        $tableStructure['columns'][] = ['name' => 'published_at', 'type' => 'timestamp'];

        $this->generator->generate(
            'testing',
            'posts',
            $tableStructure,
            [],
            $config
        );

        $modelPath = $this->getModelPath('Post');
        $content = File::get($modelPath);
        
        $this->assertStringContainsString('public function scopeStatus($query, string $status)', $content);
        $this->assertStringContainsString('public function scopeActive($query)', $content);
        $this->assertStringContainsString('public function scopePublished($query)', $content);
    }

    /** @test */
    public function it_generates_accessors_and_mutators_when_configured(): void
    {
        $config = array_merge($this->getBasicConfig(), ['with_validation' => true]);
        $tableStructure = $this->getBasicTableStructure();
        $tableStructure['columns'][] = ['name' => 'first_name', 'type' => 'varchar'];
        $tableStructure['columns'][] = ['name' => 'last_name', 'type' => 'varchar'];
        $tableStructure['columns'][] = ['name' => 'password', 'type' => 'varchar'];

        $this->generator->generate(
            'testing',
            'users',
            $tableStructure,
            [],
            $config
        );

        $modelPath = $this->getModelPath('User');
        $content = File::get($modelPath);
        
        // Check accessor
        $this->assertStringContainsString('protected function fullName(): Attribute', $content);
        $this->assertStringContainsString('use Illuminate\\Database\\Eloquent\\Casts\\Attribute', $content);
        
        // Check mutator
        $this->assertStringContainsString('protected function password(): Attribute', $content);
        $this->assertStringContainsString('set: fn (string $value) => bcrypt($value)', $content);
    }

    /** @test */
    public function it_handles_custom_namespace_and_directory(): void
    {
        $config = [
            'namespace' => 'App\\Models\\Blog',
            'dir' => 'Blog',
        ];

        $this->generator->generate(
            'testing',
            'posts',
            $this->getBasicTableStructure('posts'),
            [],
            $config
        );

        $expectedPath = app_path('Models/Blog/Post.php');
        $this->assertFileExists($expectedPath);
        
        $content = File::get($expectedPath);
        $this->assertStringContainsString('namespace App\\Models\\Blog', $content);
    }

    /** @test */
    public function it_generates_class_documentation(): void
    {
        $tableStructure = [
            'name' => 'users',
            'columns' => [
                ['name' => 'id', 'type' => 'bigint', 'nullable' => false],
                ['name' => 'name', 'type' => 'varchar', 'nullable' => false],
                ['name' => 'email', 'type' => 'varchar', 'nullable' => true],
            ],
            'primary_key' => ['columns' => ['id']],
            'has_timestamps' => true,
            'has_soft_deletes' => false,
            'indexes' => [
                ['name' => 'users_email_index', 'columns' => ['email']],
            ],
        ];

        $this->generator->generate(
            'testing',
            'users',
            $tableStructure,
            [],
            $this->getBasicConfig()
        );

        $modelPath = $this->getModelPath('User');
        $content = File::get($modelPath);
        
        $this->assertStringContainsString('* @property-read int $id', $content);
        $this->assertStringContainsString('* @property string $name', $content);
        $this->assertStringContainsString('* @property ?string $email', $content);
        $this->assertStringContainsString('* Indexes:', $content);
        $this->assertStringContainsString('* - users_email_index (email)', $content);
    }

    /** @test */
    public function it_handles_different_primary_key_types(): void
    {
        $tableStructure = [
            'name' => 'tokens',
            'columns' => [
                ['name' => 'token', 'type' => 'varchar', 'auto_increment' => false],
                ['name' => 'data', 'type' => 'text'],
            ],
            'primary_key' => ['columns' => ['token']],
            'has_timestamps' => false,
            'has_soft_deletes' => false,
        ];

        $this->generator->generate(
            'testing',
            'tokens',
            $tableStructure,
            [],
            $this->getBasicConfig()
        );

        $modelPath = $this->getModelPath('Token');
        $content = File::get($modelPath);
        
        $this->assertStringContainsString("protected readonly string \$primaryKey = 'token'", $content);
        $this->assertStringContainsString('public readonly bool $incrementing = false', $content);
        $this->assertStringContainsString("protected readonly string \$keyType = 'string'", $content);
        $this->assertStringContainsString('public $timestamps = false', $content);
    }

    /** @test */
    public function it_creates_model_directory_if_not_exists(): void
    {
        $config = ['dir' => 'Admin/Blog'];
        
        $this->generator->generate(
            'testing',
            'posts',
            $this->getBasicTableStructure('posts'),
            [],
            $config
        );

        $expectedPath = app_path('Models/Admin/Blog/Post.php');
        $this->assertFileExists($expectedPath);
        $this->assertDirectoryExists(app_path('Models/Admin/Blog'));
    }

    private function getBasicTableStructure(string $tableName = 'users'): array
    {
        return [
            'name' => $tableName,
            'columns' => [
                ['name' => 'id', 'type' => 'bigint', 'nullable' => false, 'auto_increment' => true],
                ['name' => 'name', 'type' => 'varchar', 'nullable' => false],
                ['name' => 'email', 'type' => 'varchar', 'nullable' => false],
                ['name' => 'created_at', 'type' => 'timestamp', 'nullable' => true],
                ['name' => 'updated_at', 'type' => 'timestamp', 'nullable' => true],
            ],
            'primary_key' => ['columns' => ['id']],
            'has_timestamps' => true,
            'has_soft_deletes' => false,
            'indexes' => [],
        ];
    }

    private function getBasicConfig(): array
    {
        return [
            'namespace' => 'App\\Models',
            'with_validation' => false,
            'with_scopes' => false,
        ];
    }
}