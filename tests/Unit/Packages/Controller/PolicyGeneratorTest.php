<?php

declare(strict_types=1);

namespace AutoGen\Tests\Unit\Packages\Controller;

use AutoGen\Packages\Controller\PolicyGenerator;
use AutoGen\Tests\TestCase;
use Illuminate\Support\Facades\File;

class PolicyGeneratorTest extends TestCase
{
    private PolicyGenerator $generator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->generator = new PolicyGenerator();
    }

    /** @test */
    public function it_can_generate_basic_policy(): void
    {
        $files = $this->generator->generate('User');

        $this->assertCount(1, $files);
        $policyPath = app_path('Policies/UserPolicy.php');
        $this->assertFileGenerated($policyPath, [
            'class UserPolicy',
            'public function viewAny(User $user): bool',
            'public function view(User $user, User $model): bool',
            'public function create(User $user): bool',
            'public function update(User $user, User $model): bool',
            'public function delete(User $user, User $model): bool',
        ]);
    }

    /** @test */
    public function it_generates_correct_namespace_and_imports(): void
    {
        $files = $this->generator->generate('Post');
        $content = File::get($files[0]);

        $this->assertStringContainsString('namespace App\\Policies', $content);
        $this->assertStringContainsString('use App\\Models\\Post', $content);
        $this->assertStringContainsString('use App\\Models\\User', $content);
        $this->assertStringContainsString('use Illuminate\\Auth\\Access\\HandlesAuthorization', $content);
    }

    /** @test */
    public function it_can_generate_policy_with_soft_deletes(): void
    {
        $files = $this->generator->generate('Post', true);
        $content = File::get($files[0]);

        $this->assertStringContainsString('public function restore(User $user, Post $model): bool', $content);
        $this->assertStringContainsString('public function forceDelete(User $user, Post $model): bool', $content);
    }

    /** @test */
    public function it_generates_policy_methods_with_proper_logic(): void
    {
        $files = $this->generator->generate('Post');
        $content = File::get($files[0]);

        // Check viewAny method
        $this->assertStringContainsString('public function viewAny(User $user): bool', $content);
        $this->assertStringContainsString('return true;', $content);

        // Check view method
        $this->assertStringContainsString('public function view(User $user, Post $model): bool', $content);

        // Check create method
        $this->assertStringContainsString('public function create(User $user): bool', $content);

        // Check update method
        $this->assertStringContainsString('public function update(User $user, Post $model): bool', $content);

        // Check delete method
        $this->assertStringContainsString('public function delete(User $user, Post $model): bool', $content);
    }

    /** @test */
    public function it_can_generate_policy_with_ownership_logic(): void
    {
        $files = $this->generator->generate('Post', false, true);
        $content = File::get($files[0]);

        $this->assertStringContainsString('return $user->id === $model->user_id;', $content);
    }

    /** @test */
    public function it_can_generate_policy_with_role_based_logic(): void
    {
        $files = $this->generator->generate('Post', false, false, true);
        $content = File::get($files[0]);

        $this->assertStringContainsString('return $user->hasRole(\'admin\')', $content);
        $this->assertStringContainsString('return $user->hasRole([\'admin\', \'editor\'])', $content);
    }

    /** @test */
    public function it_handles_nested_model_names(): void
    {
        $files = $this->generator->generate('Admin/User');
        
        $policyPath = app_path('Policies/Admin/UserPolicy.php');
        $this->assertFileGenerated($policyPath, [
            'namespace App\\Policies\\Admin',
            'use App\\Models\\Admin\\User',
            'class UserPolicy',
        ]);
    }

    /** @test */
    public function it_generates_proper_method_documentation(): void
    {
        $files = $this->generator->generate('Post');
        $content = File::get($files[0]);

        $this->assertStringContainsString('/**', $content);
        $this->assertStringContainsString('* Determine whether the user can view any models.', $content);
        $this->assertStringContainsString('* Determine whether the user can view the model.', $content);
        $this->assertStringContainsString('* Determine whether the user can create models.', $content);
        $this->assertStringContainsString('* Determine whether the user can update the model.', $content);
        $this->assertStringContainsString('* Determine whether the user can delete the model.', $content);
    }

    /** @test */
    public function it_can_generate_before_method(): void
    {
        $files = $this->generator->generate('Post', false, false, false, true);
        $content = File::get($files[0]);

        $this->assertStringContainsString('public function before(User $user, string $ability): bool|null', $content);
        $this->assertStringContainsString('if ($user->hasRole(\'super-admin\')) {', $content);
        $this->assertStringContainsString('return true;', $content);
        $this->assertStringContainsString('return null;', $content);
    }

    /** @test */
    public function it_generates_custom_methods_for_specific_models(): void
    {
        $files = $this->generator->generate('User');
        $content = File::get($files[0]);

        // For User model, should include profile-related methods
        $this->assertStringContainsString('public function viewProfile(User $user, User $model): bool', $content);
        $this->assertStringContainsString('public function updateProfile(User $user, User $model): bool', $content);
    }

    /** @test */
    public function it_can_generate_policy_with_custom_user_model(): void
    {
        $files = $this->generator->generate('Post', false, false, false, false, 'App\\Models\\Admin');
        $content = File::get($files[0]);

        $this->assertStringContainsString('use App\\Models\\Admin;', $content);
        $this->assertStringContainsString('public function viewAny(Admin $user): bool', $content);
        $this->assertStringContainsString('public function view(Admin $user, Post $model): bool', $content);
    }

    /** @test */
    public function it_generates_resource_specific_methods(): void
    {
        // For different models, different methods might be relevant
        $files = $this->generator->generate('Order');
        $content = File::get($files[0]);

        $this->assertStringContainsString('public function cancel(User $user, Order $model): bool', $content);
        $this->assertStringContainsString('public function refund(User $user, Order $model): bool', $content);
    }

    /** @test */
    public function it_handles_policy_generation_with_force_option(): void
    {
        // Create existing policy
        $policyPath = app_path('Policies/UserPolicy.php');
        File::ensureDirectoryExists(dirname($policyPath));
        File::put($policyPath, '<?php // Existing policy');

        $files = $this->generator->generate('User', false, false, false, false, null, true);

        $content = File::get($files[0]);
        $this->assertStringNotContainsString('// Existing policy', $content);
        $this->assertStringContainsString('class UserPolicy', $content);
    }

    /** @test */
    public function it_throws_exception_when_policy_exists_without_force(): void
    {
        // Create existing policy
        $policyPath = app_path('Policies/UserPolicy.php');
        File::ensureDirectoryExists(dirname($policyPath));
        File::put($policyPath, '<?php // Existing policy');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Policy already exists');

        $this->generator->generate('User');
    }

    /** @test */
    public function it_generates_policy_with_proper_return_types(): void
    {
        $files = $this->generator->generate('Post');
        $content = File::get($files[0]);

        $this->assertStringContainsString(': bool', $content);
        $this->assertStringContainsString(': bool|null', $content);
    }

    /** @test */
    public function it_can_generate_minimal_policy(): void
    {
        $files = $this->generator->generateMinimal('Post');
        $content = File::get($files[0]);

        // Minimal policy should have basic CRUD methods only
        $this->assertStringContainsString('public function view(User $user, Post $model): bool', $content);
        $this->assertStringContainsString('public function create(User $user): bool', $content);
        $this->assertStringContainsString('public function update(User $user, Post $model): bool', $content);
        $this->assertStringContainsString('public function delete(User $user, Post $model): bool', $content);

        // Should not contain advanced methods
        $this->assertStringNotContainsString('public function before(', $content);
        $this->assertStringNotContainsString('hasRole', $content);
    }

    /** @test */
    public function it_generates_policy_class_with_proper_structure(): void
    {
        $files = $this->generator->generate('Category');
        $content = File::get($files[0]);

        // Check PHP opening tag
        $this->assertStringStartsWith('<?php', $content);

        // Check strict types declaration
        $this->assertStringContainsString('declare(strict_types=1);', $content);

        // Check namespace
        $this->assertStringContainsString('namespace App\\Policies;', $content);

        // Check class declaration
        $this->assertStringContainsString('class CategoryPolicy', $content);

        // Check trait usage
        $this->assertStringContainsString('use HandlesAuthorization;', $content);
    }

    /** @test */
    public function it_can_customize_policy_template(): void
    {
        $customTemplate = [
            'with_comments' => false,
            'with_before_method' => false,
            'methods' => ['view', 'create', 'update'],
        ];

        $files = $this->generator->generateWithTemplate('Post', $customTemplate);
        $content = File::get($files[0]);

        $this->assertStringContainsString('public function view(User $user, Post $model): bool', $content);
        $this->assertStringContainsString('public function create(User $user): bool', $content);
        $this->assertStringContainsString('public function update(User $user, Post $model): bool', $content);

        // Should not contain delete method or comments
        $this->assertStringNotContainsString('public function delete(User $user, Post $model): bool', $content);
        $this->assertStringNotContainsString('/**', $content);
    }
}