<?php

declare(strict_types=1);

namespace AutoGen\Tests\Unit\Packages\Controller;

use AutoGen\Packages\Controller\FormRequestGenerator;
use AutoGen\Tests\TestCase;
use Illuminate\Support\Facades\File;

class FormRequestGeneratorTest extends TestCase
{
    private FormRequestGenerator $generator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->generator = new FormRequestGenerator();
    }

    /** @test */
    public function it_can_generate_store_request(): void
    {
        $tableStructure = $this->getTableStructure();
        
        $files = $this->generator->generateStoreRequest('User', $tableStructure);

        $this->assertCount(1, $files);
        $requestPath = app_path('Http/Requests/StoreUserRequest.php');
        $this->assertFileGenerated($requestPath, [
            'class StoreUserRequest extends FormRequest',
            'public function authorize(): bool',
            'public function rules(): array',
            "'name' => 'required|string|max:255'",
            "'email' => 'required|string|email|max:255|unique:users'",
        ]);
    }

    /** @test */
    public function it_can_generate_update_request(): void
    {
        $tableStructure = $this->getTableStructure();
        
        $files = $this->generator->generateUpdateRequest('User', $tableStructure);

        $this->assertCount(1, $files);
        $requestPath = app_path('Http/Requests/UpdateUserRequest.php');
        $this->assertFileGenerated($requestPath, [
            'class UpdateUserRequest extends FormRequest',
            'public function authorize(): bool',
            'public function rules(): array',
            "'name' => 'sometimes|required|string|max:255'",
            "'email' => 'sometimes|required|string|email|max:255|unique:users,email,' . \$this->route('user')",
        ]);
    }

    /** @test */
    public function it_generates_correct_validation_rules_for_different_types(): void
    {
        $tableStructure = [
            'columns' => [
                ['name' => 'title', 'type' => 'string', 'nullable' => false, 'length' => 100],
                ['name' => 'description', 'type' => 'text', 'nullable' => true],
                ['name' => 'age', 'type' => 'integer', 'nullable' => false],
                ['name' => 'price', 'type' => 'decimal', 'nullable' => true, 'precision' => 8, 'scale' => 2],
                ['name' => 'is_active', 'type' => 'boolean', 'nullable' => false],
                ['name' => 'birth_date', 'type' => 'date', 'nullable' => true],
                ['name' => 'published_at', 'type' => 'datetime', 'nullable' => true],
                ['name' => 'tags', 'type' => 'json', 'nullable' => true],
                ['name' => 'status', 'type' => 'enum', 'options' => ['draft', 'published', 'archived']],
            ],
        ];

        $files = $this->generator->generateStoreRequest('Post', $tableStructure);
        $content = File::get($files[0]);

        // String validation
        $this->assertStringContainsString("'title' => 'required|string|max:100'", $content);
        
        // Text validation
        $this->assertStringContainsString("'description' => 'nullable|string'", $content);
        
        // Integer validation
        $this->assertStringContainsString("'age' => 'required|integer'", $content);
        
        // Decimal validation
        $this->assertStringContainsString("'price' => 'nullable|numeric'", $content);
        
        // Boolean validation
        $this->assertStringContainsString("'is_active' => 'required|boolean'", $content);
        
        // Date validation
        $this->assertStringContainsString("'birth_date' => 'nullable|date'", $content);
        
        // DateTime validation
        $this->assertStringContainsString("'published_at' => 'nullable|date'", $content);
        
        // JSON validation
        $this->assertStringContainsString("'tags' => 'nullable|array'", $content);
        
        // Enum validation
        $this->assertStringContainsString("'status' => 'required|in:draft,published,archived'", $content);
    }

    /** @test */
    public function it_handles_foreign_key_validation(): void
    {
        $tableStructure = [
            'columns' => [
                ['name' => 'title', 'type' => 'string', 'nullable' => false],
                ['name' => 'user_id', 'type' => 'integer', 'nullable' => false, 'foreign_key' => 'users.id'],
                ['name' => 'category_id', 'type' => 'integer', 'nullable' => true, 'foreign_key' => 'categories.id'],
            ],
        ];

        $files = $this->generator->generateStoreRequest('Post', $tableStructure);
        $content = File::get($files[0]);

        $this->assertStringContainsString("'user_id' => 'required|integer|exists:users,id'", $content);
        $this->assertStringContainsString("'category_id' => 'nullable|integer|exists:categories,id'", $content);
    }

    /** @test */
    public function it_excludes_auto_increment_and_timestamp_fields(): void
    {
        $tableStructure = [
            'columns' => [
                ['name' => 'id', 'type' => 'integer', 'auto_increment' => true],
                ['name' => 'name', 'type' => 'string', 'nullable' => false],
                ['name' => 'created_at', 'type' => 'datetime', 'nullable' => true],
                ['name' => 'updated_at', 'type' => 'datetime', 'nullable' => true],
            ],
        ];

        $files = $this->generator->generateStoreRequest('User', $tableStructure);
        $content = File::get($files[0]);

        $this->assertStringNotContainsString("'id'", $content);
        $this->assertStringNotContainsString("'created_at'", $content);
        $this->assertStringNotContainsString("'updated_at'", $content);
        $this->assertStringContainsString("'name'", $content);
    }

    /** @test */
    public function it_can_generate_custom_validation_messages(): void
    {
        $tableStructure = $this->getTableStructure();
        
        $files = $this->generator->generateStoreRequest('User', $tableStructure, true);
        $content = File::get($files[0]);

        $this->assertStringContainsString('public function messages(): array', $content);
        $this->assertStringContainsString("'name.required' => 'The name field is required.'", $content);
        $this->assertStringContainsString("'email.unique' => 'This email address is already taken.'", $content);
    }

    /** @test */
    public function it_can_generate_custom_attribute_names(): void
    {
        $tableStructure = $this->getTableStructure();
        
        $files = $this->generator->generateStoreRequest('User', $tableStructure, true);
        $content = File::get($files[0]);

        $this->assertStringContainsString('public function attributes(): array', $content);
        $this->assertStringContainsString("'name' => 'full name'", $content);
        $this->assertStringContainsString("'email' => 'email address'", $content);
    }

    /** @test */
    public function it_generates_authorization_logic(): void
    {
        $tableStructure = $this->getTableStructure();
        
        $files = $this->generator->generateStoreRequest('User', $tableStructure);
        $content = File::get($files[0]);

        $this->assertStringContainsString('public function authorize(): bool', $content);
        $this->assertStringContainsString('return true;', $content);
    }

    /** @test */
    public function it_can_generate_with_policy_authorization(): void
    {
        $tableStructure = $this->getTableStructure();
        
        $files = $this->generator->generateStoreRequest('User', $tableStructure, false, true);
        $content = File::get($files[0]);

        $this->assertStringContainsString("return \$this->user()->can('create', User::class);", $content);
        $this->assertStringContainsString('use App\\Models\\User;', $content);
    }

    /** @test */
    public function it_handles_nested_model_names(): void
    {
        $tableStructure = $this->getTableStructure();
        
        $files = $this->generator->generateStoreRequest('Admin/User', $tableStructure);
        
        $requestPath = app_path('Http/Requests/Admin/StoreUserRequest.php');
        $this->assertFileGenerated($requestPath, [
            'namespace App\\Http\\Requests\\Admin',
            'class StoreUserRequest extends FormRequest',
        ]);
    }

    /** @test */
    public function it_generates_correct_unique_rule_for_update_request(): void
    {
        $tableStructure = [
            'name' => 'users',
            'columns' => [
                ['name' => 'name', 'type' => 'string', 'nullable' => false],
                ['name' => 'email', 'type' => 'string', 'nullable' => false, 'unique' => true],
                ['name' => 'username', 'type' => 'string', 'nullable' => false, 'unique' => true],
            ],
        ];

        $files = $this->generator->generateUpdateRequest('User', $tableStructure);
        $content = File::get($files[0]);

        $this->assertStringContainsString("'email' => 'sometimes|required|string|email|max:255|unique:users,email,' . \$this->route('user')", $content);
        $this->assertStringContainsString("'username' => 'sometimes|required|string|max:255|unique:users,username,' . \$this->route('user')", $content);
    }

    /** @test */
    public function it_can_generate_file_upload_validation(): void
    {
        $tableStructure = [
            'columns' => [
                ['name' => 'name', 'type' => 'string', 'nullable' => false],
                ['name' => 'avatar', 'type' => 'string', 'nullable' => true, 'is_file' => true],
                ['name' => 'document', 'type' => 'string', 'nullable' => false, 'is_file' => true],
            ],
        ];

        $files = $this->generator->generateStoreRequest('User', $tableStructure);
        $content = File::get($files[0]);

        $this->assertStringContainsString("'avatar' => 'nullable|file|image|max:2048'", $content);
        $this->assertStringContainsString("'document' => 'required|file|mimes:pdf,doc,docx|max:10240'", $content);
    }

    /** @test */
    public function it_generates_conditional_validation_rules(): void
    {
        $tableStructure = [
            'columns' => [
                ['name' => 'type', 'type' => 'enum', 'options' => ['individual', 'company']],
                ['name' => 'first_name', 'type' => 'string', 'nullable' => true],
                ['name' => 'last_name', 'type' => 'string', 'nullable' => true],
                ['name' => 'company_name', 'type' => 'string', 'nullable' => true],
            ],
        ];

        $files = $this->generator->generateStoreRequest('Customer', $tableStructure, false, false, true);
        $content = File::get($files[0]);

        $this->assertStringContainsString("'first_name' => 'required_if:type,individual|string|max:255'", $content);
        $this->assertStringContainsString("'last_name' => 'required_if:type,individual|string|max:255'", $content);
        $this->assertStringContainsString("'company_name' => 'required_if:type,company|string|max:255'", $content);
    }

    /** @test */
    public function it_handles_password_confirmation(): void
    {
        $tableStructure = [
            'columns' => [
                ['name' => 'name', 'type' => 'string', 'nullable' => false],
                ['name' => 'email', 'type' => 'string', 'nullable' => false],
                ['name' => 'password', 'type' => 'string', 'nullable' => false],
            ],
        ];

        $files = $this->generator->generateStoreRequest('User', $tableStructure);
        $content = File::get($files[0]);

        $this->assertStringContainsString("'password' => 'required|string|min:8|confirmed'", $content);
    }

    /** @test */
    public function it_generates_array_validation_for_json_fields(): void
    {
        $tableStructure = [
            'columns' => [
                ['name' => 'name', 'type' => 'string', 'nullable' => false],
                ['name' => 'settings', 'type' => 'json', 'nullable' => true],
                ['name' => 'tags', 'type' => 'json', 'nullable' => false],
            ],
        ];

        $files = $this->generator->generateStoreRequest('Post', $tableStructure);
        $content = File::get($files[0]);

        $this->assertStringContainsString("'settings' => 'nullable|array'", $content);
        $this->assertStringContainsString("'tags' => 'required|array'", $content);
        $this->assertStringContainsString("'tags.*' => 'string'", $content);
    }

    private function getTableStructure(): array
    {
        return [
            'name' => 'users',
            'columns' => [
                ['name' => 'name', 'type' => 'string', 'nullable' => false, 'length' => 255],
                ['name' => 'email', 'type' => 'string', 'nullable' => false, 'length' => 255, 'unique' => true],
                ['name' => 'password', 'type' => 'string', 'nullable' => false],
            ],
        ];
    }
}