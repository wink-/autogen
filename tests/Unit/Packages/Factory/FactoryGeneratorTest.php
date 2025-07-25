<?php

declare(strict_types=1);

namespace AutoGen\Tests\Unit\Packages\Factory;

use AutoGen\Packages\Factory\FactoryGenerator;
use AutoGen\Packages\Factory\FakeDataMapper;
use AutoGen\Packages\Factory\RelationshipFactoryHandler;
use AutoGen\Packages\Model\DatabaseIntrospector;
use AutoGen\Tests\TestCase;
use AutoGen\Tests\Helpers\MockHelper;
use Illuminate\Support\Facades\File;
use Mockery;

class FactoryGeneratorTest extends TestCase
{
    private FactoryGenerator $generator;
    private FakeDataMapper $fakeDataMapper;
    private RelationshipFactoryHandler $relationshipHandler;
    private DatabaseIntrospector $introspector;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->fakeDataMapper = new FakeDataMapper();
        $this->relationshipHandler = new RelationshipFactoryHandler();
        $this->introspector = Mockery::mock(DatabaseIntrospector::class);
        
        $this->generator = new FactoryGenerator(
            $this->fakeDataMapper,
            $this->relationshipHandler,
            $this->introspector
        );
    }

    /** @test */
    public function it_can_generate_basic_factory(): void
    {
        $this->mockIntrospector();
        
        $result = $this->generator->generate('User', $this->getBasicConfig());

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('path', $result);
        
        $factoryPath = $result['path'];
        $this->assertFileExists($factoryPath);
        
        $content = File::get($factoryPath);
        $this->assertStringContainsString('class UserFactory extends Factory', $content);
        $this->assertStringContainsString('use App\\Models\\User', $content);
        $this->assertStringContainsString('public function definition(): array', $content);
    }

    /** @test */
    public function it_generates_correct_fake_data_for_fields(): void
    {
        $this->mockIntrospector();
        
        $result = $this->generator->generate('User', $this->getBasicConfig());
        $content = File::get($result['path']);

        $this->assertStringContainsString("'name' => \$this->faker->name()", $content);
        $this->assertStringContainsString("'email' => \$this->faker->unique()->safeEmail()", $content);
        $this->assertStringContainsString("'password' => bcrypt('password')", $content);
    }

    /** @test */
    public function it_can_generate_factory_with_states(): void
    {
        $this->mockIntrospector();
        
        $config = array_merge($this->getBasicConfig(), ['with_states' => true]);
        $result = $this->generator->generate('User', $config);
        
        $content = File::get($result['path']);
        $this->assertStringContainsString('public function active(): static', $content);
        $this->assertStringContainsString('public function inactive(): static', $content);
        $this->assertStringContainsString('public function verified(): static', $content);
        $this->assertStringContainsString('public function unverified(): static', $content);
    }

    /** @test */
    public function it_can_generate_factory_with_relationships(): void
    {
        $this->mockIntrospectorWithRelationships();
        
        $config = array_merge($this->getBasicConfig(), ['with_relationships' => true]);
        $result = $this->generator->generate('Post', $config);
        
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('relationships', $result);
        $this->assertNotEmpty($result['relationships']);
        
        // The relationship handler would generate the relationship methods
        // This is tested separately in RelationshipFactoryHandlerTest
    }

    /** @test */
    public function it_handles_non_existent_model(): void
    {
        $result = $this->generator->generate('NonExistentModel', $this->getBasicConfig());

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
        $this->assertStringContainsString('Model class not found', $result['error']);
    }

    /** @test */
    public function it_generates_different_templates(): void
    {
        $this->mockIntrospector();
        
        // Test minimal template
        $minimalConfig = array_merge($this->getBasicConfig(), ['template' => 'minimal']);
        $result1 = $this->generator->generate('User', $minimalConfig);
        $minimalContent = File::get($result1['path']);
        
        // Test advanced template
        $advancedConfig = array_merge($this->getBasicConfig(), [
            'template' => 'advanced',
            'with_states' => true,
            'with_relationships' => true,
        ]);
        $result2 = $this->generator->generate('Post', $advancedConfig);
        
        $this->assertTrue($result1['success']);
        $this->assertTrue($result2['success']);
        
        // Minimal should be simpler
        $this->assertStringNotContainsString('public function active()', $minimalContent);
    }

    /** @test */
    public function it_can_handle_different_locales(): void
    {
        $this->mockIntrospector();
        
        $config = array_merge($this->getBasicConfig(), ['locale' => 'es_ES']);
        $result = $this->generator->generate('User', $config);
        
        $this->assertTrue($result['success']);
        $content = File::get($result['path']);
        
        // Should include Faker import for non-default locale
        $this->assertStringContainsString('use Faker\\Generator as Faker', $content);
    }

    /** @test */
    public function it_generates_correct_factory_path(): void
    {
        $path = $this->generator->getFactoryPath('User');
        $expectedPath = database_path('factories/UserFactory.php');
        
        $this->assertEquals($expectedPath, $path);
    }

    /** @test */
    public function it_creates_factory_directory_if_not_exists(): void
    {
        $this->mockIntrospector();
        
        // Remove factories directory if it exists
        $factoriesDir = database_path('factories');
        if (File::exists($factoriesDir)) {
            File::deleteDirectory($factoriesDir);
        }
        
        $result = $this->generator->generate('User', $this->getBasicConfig());
        
        $this->assertTrue($result['success']);
        $this->assertDirectoryExists($factoriesDir);
    }

    /** @test */
    public function it_handles_boolean_fields_correctly(): void
    {
        $this->introspector->shouldReceive('introspectTable')
            ->andReturn([
                'columns' => [
                    ['name' => 'id', 'type' => 'bigint', 'nullable' => false],
                    ['name' => 'is_active', 'type' => 'boolean', 'nullable' => false, 'default' => true],
                    ['name' => 'is_verified', 'type' => 'tinyint(1)', 'nullable' => false, 'default' => false],
                    ['name' => 'created_at', 'type' => 'timestamp', 'nullable' => true],
                    ['name' => 'updated_at', 'type' => 'timestamp', 'nullable' => true],
                ],
            ]);
        
        $config = array_merge($this->getBasicConfig(), ['with_states' => true]);
        $result = $this->generator->generate('User', $config);
        
        $content = File::get($result['path']);
        $this->assertStringContainsString("'is_active' => \$this->faker->boolean()", $content);
        $this->assertStringContainsString("'is_verified' => \$this->faker->boolean()", $content);
        
        // Should generate states for boolean fields
        $this->assertStringContainsString('public function active(): static', $content);
        $this->assertStringContainsString('public function verified(): static', $content);
    }

    /** @test */
    public function it_handles_enum_fields_correctly(): void
    {
        $this->introspector->shouldReceive('introspectTable')
            ->andReturn([
                'columns' => [
                    ['name' => 'id', 'type' => 'bigint', 'nullable' => false],
                    ['name' => 'status', 'type' => 'enum', 'nullable' => false, 'options' => ['active', 'inactive', 'pending']],
                    ['name' => 'role', 'type' => 'enum', 'nullable' => false, 'options' => ['admin', 'user', 'guest']],
                ],
            ]);
        
        $result = $this->generator->generate('User', $this->getBasicConfig());
        
        $content = File::get($result['path']);
        $this->assertStringContainsString("'status' => \$this->faker->randomElement(['active', 'inactive', 'pending'])", $content);
        $this->assertStringContainsString("'role' => \$this->faker->randomElement(['admin', 'user', 'guest'])", $content);
    }

    /** @test */
    public function it_handles_date_fields_correctly(): void
    {
        $this->introspector->shouldReceive('introspectTable')
            ->andReturn([
                'columns' => [
                    ['name' => 'id', 'type' => 'bigint', 'nullable' => false],
                    ['name' => 'birth_date', 'type' => 'date', 'nullable' => true],
                    ['name' => 'published_at', 'type' => 'datetime', 'nullable' => true],
                    ['name' => 'last_login', 'type' => 'timestamp', 'nullable' => true],
                ],
            ]);
        
        $result = $this->generator->generate('User', $this->getBasicConfig());
        
        $content = File::get($result['path']);
        $this->assertStringContainsString("'birth_date' => \$this->faker->date()", $content);
        $this->assertStringContainsString("'published_at' => \$this->faker->dateTime()", $content);
        $this->assertStringContainsString("'last_login' => \$this->faker->dateTime()", $content);
    }

    /** @test */
    public function it_handles_numeric_fields_correctly(): void
    {
        $this->introspector->shouldReceive('introspectTable')
            ->andReturn([
                'columns' => [
                    ['name' => 'id', 'type' => 'bigint', 'nullable' => false],
                    ['name' => 'age', 'type' => 'integer', 'nullable' => true],
                    ['name' => 'salary', 'type' => 'decimal', 'nullable' => true, 'precision' => 10, 'scale' => 2],
                    ['name' => 'rating', 'type' => 'float', 'nullable' => true],
                ],
            ]);
        
        $result = $this->generator->generate('User', $this->getBasicConfig());
        
        $content = File::get($result['path']);
        $this->assertStringContainsString("'age' => \$this->faker->numberBetween(18, 100)", $content);
        $this->assertStringContainsString("'salary' => \$this->faker->randomFloat(2, 30000, 150000)", $content);
        $this->assertStringContainsString("'rating' => \$this->faker->randomFloat(1, 0, 5)", $content);
    }

    /** @test */
    public function it_handles_text_fields_correctly(): void
    {
        $this->introspector->shouldReceive('introspectTable')
            ->andReturn([
                'columns' => [
                    ['name' => 'id', 'type' => 'bigint', 'nullable' => false],
                    ['name' => 'title', 'type' => 'varchar', 'nullable' => false, 'length' => 255],
                    ['name' => 'description', 'type' => 'text', 'nullable' => true],
                    ['name' => 'bio', 'type' => 'longtext', 'nullable' => true],
                ],
            ]);
        
        $result = $this->generator->generate('Post', $this->getBasicConfig());
        
        $content = File::get($result['path']);
        $this->assertStringContainsString("'title' => \$this->faker->sentence()", $content);
        $this->assertStringContainsString("'description' => \$this->faker->paragraph()", $content);
        $this->assertStringContainsString("'bio' => \$this->faker->text()", $content);
    }

    /** @test */
    public function it_handles_json_fields_correctly(): void
    {
        $this->introspector->shouldReceive('introspectTable')
            ->andReturn([
                'columns' => [
                    ['name' => 'id', 'type' => 'bigint', 'nullable' => false],
                    ['name' => 'metadata', 'type' => 'json', 'nullable' => true],
                    ['name' => 'settings', 'type' => 'jsonb', 'nullable' => true],
                ],
            ]);
        
        $result = $this->generator->generate('User', $this->getBasicConfig());
        
        $content = File::get($result['path']);
        $this->assertStringContainsString("'metadata' => []", $content);
        $this->assertStringContainsString("'settings' => []", $content);
    }

    /** @test */
    public function it_fallback_to_model_fillable_when_introspection_fails(): void
    {
        $this->introspector->shouldReceive('introspectTable')
            ->andThrow(new \Exception('Database connection failed'));
        
        $result = $this->generator->generate('User', $this->getBasicConfig());
        
        // Should still succeed by falling back to model's fillable property
        $this->assertTrue($result['success']);
        
        $content = File::get($result['path']);
        $this->assertStringContainsString('class UserFactory extends Factory', $content);
    }

    /** @test */
    public function it_generates_appropriate_states_for_status_fields(): void
    {
        $this->introspector->shouldReceive('introspectTable')
            ->andReturn([
                'columns' => [
                    ['name' => 'id', 'type' => 'bigint', 'nullable' => false],
                    ['name' => 'status', 'type' => 'varchar', 'nullable' => false],
                    ['name' => 'user_status', 'type' => 'varchar', 'nullable' => false],
                    ['name' => 'account_status', 'type' => 'varchar', 'nullable' => false],
                ],
            ]);
        
        $config = array_merge($this->getBasicConfig(), ['with_states' => true]);
        $result = $this->generator->generate('User', $config);
        
        $content = File::get($result['path']);
        $this->assertStringContainsString('public function active(): static', $content);
        $this->assertStringContainsString('public function inactive(): static', $content);
        $this->assertStringContainsString("'status' => 'active'", $content);
        $this->assertStringContainsString("'status' => 'inactive'", $content);
    }

    /** @test */
    public function it_handles_file_upload_fields(): void
    {
        $this->introspector->shouldReceive('introspectTable')
            ->andReturn([
                'columns' => [
                    ['name' => 'id', 'type' => 'bigint', 'nullable' => false],
                    ['name' => 'avatar', 'type' => 'varchar', 'nullable' => true],
                    ['name' => 'document_path', 'type' => 'varchar', 'nullable' => true],
                    ['name' => 'image_url', 'type' => 'varchar', 'nullable' => true],
                ],
            ]);
        
        $config = array_merge($this->getBasicConfig(), ['with_files' => true]);
        $result = $this->generator->generate('User', $config);
        
        $content = File::get($result['path']);
        $this->assertStringContainsString("'avatar' => \$this->faker->imageUrl()", $content);
        $this->assertStringContainsString("'document_path' => \$this->faker->filePath()", $content);
        $this->assertStringContainsString("'image_url' => \$this->faker->imageUrl()", $content);
    }

    private function mockIntrospector(): void
    {
        $this->introspector->shouldReceive('introspectTable')
            ->andReturn([
                'columns' => [
                    ['name' => 'id', 'type' => 'bigint', 'nullable' => false],
                    ['name' => 'name', 'type' => 'varchar', 'nullable' => false, 'length' => 255],
                    ['name' => 'email', 'type' => 'varchar', 'nullable' => false, 'length' => 255],
                    ['name' => 'password', 'type' => 'varchar', 'nullable' => false, 'length' => 255],
                    ['name' => 'is_active', 'type' => 'boolean', 'nullable' => false, 'default' => true],
                    ['name' => 'email_verified_at', 'type' => 'timestamp', 'nullable' => true],
                    ['name' => 'created_at', 'type' => 'timestamp', 'nullable' => true],
                    ['name' => 'updated_at', 'type' => 'timestamp', 'nullable' => true],
                ],
            ]);
    }

    private function mockIntrospectorWithRelationships(): void
    {
        $this->introspector->shouldReceive('introspectTable')
            ->andReturn([
                'columns' => [
                    ['name' => 'id', 'type' => 'bigint', 'nullable' => false],
                    ['name' => 'title', 'type' => 'varchar', 'nullable' => false],
                    ['name' => 'content', 'type' => 'text', 'nullable' => false],
                    ['name' => 'user_id', 'type' => 'bigint', 'nullable' => false],
                    ['name' => 'created_at', 'type' => 'timestamp', 'nullable' => true],
                    ['name' => 'updated_at', 'type' => 'timestamp', 'nullable' => true],
                ],
            ]);
    }

    private function getBasicConfig(): array
    {
        return [
            'template' => 'default',
            'locale' => 'en_US',
            'with_states' => false,
            'with_relationships' => false,
            'with_files' => false,
        ];
    }

    protected function tearDown(): void
    {
        // Clean up generated factories
        $factoriesPath = database_path('factories');
        if (File::exists($factoriesPath)) {
            $files = File::glob($factoriesPath . '/*Factory.php');
            foreach ($files as $file) {
                File::delete($file);
            }
        }
        
        parent::tearDown();
    }
}