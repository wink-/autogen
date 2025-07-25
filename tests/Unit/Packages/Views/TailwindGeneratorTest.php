<?php

declare(strict_types=1);

namespace AutoGen\Tests\Unit\Packages\Views;

use AutoGen\Packages\Views\Generators\TailwindGenerator;
use AutoGen\Tests\TestCase;
use Illuminate\Foundation\Auth\User;

class TailwindGeneratorTest extends TestCase
{
    private TailwindGenerator $generator;

    protected function setUp(): void
    {
        parent::setUp();
        
        $user = new User();
        $user->fillable(['name', 'email', 'password']);
        
        $this->generator = new TailwindGenerator(
            modelInstance: $user,
            modelName: 'User',
            modelBaseName: 'User',
            routeName: 'users',
            layout: 'app',
            withDatatable: false,
            withSearch: false,
            withModals: false
        );
    }

    /** @test */
    public function it_can_generate_index_view_with_tailwind_classes(): void
    {
        $content = $this->generator->generateView('index');

        $this->assertStringContainsString('@extends(\'app\')', $content);
        $this->assertStringContainsString('@section(\'content\')', $content);
        $this->assertStringContainsString('bg-white', $content);
        $this->assertStringContainsString('shadow-md', $content);
        $this->assertStringContainsString('rounded-lg', $content);
        $this->assertStringContainsString('px-6', $content);
        $this->assertStringContainsString('py-4', $content);
        $this->assertStringContainsString('table-auto', $content);
        $this->assertStringContainsString('w-full', $content);
    }

    /** @test */
    public function it_can_generate_create_view(): void
    {
        $content = $this->generator->generateView('create');

        $this->assertStringContainsString('@extends(\'app\')', $content);
        $this->assertStringContainsString('Create User', $content);
        $this->assertStringContainsString('@include(\'users._form\'', $content);
        $this->assertStringContainsString('max-w-md', $content);
        $this->assertStringContainsString('mx-auto', $content);
    }

    /** @test */
    public function it_can_generate_edit_view(): void
    {
        $content = $this->generator->generateView('edit');

        $this->assertStringContainsString('@extends(\'app\')', $content);
        $this->assertStringContainsString('Edit User', $content);
        $this->assertStringContainsString('@include(\'users._form\'', $content);
        $this->assertStringContainsString('{{ $user }}', $content);
    }

    /** @test */
    public function it_can_generate_show_view(): void
    {
        $content = $this->generator->generateView('show');

        $this->assertStringContainsString('@extends(\'app\')', $content);
        $this->assertStringContainsString('User Details', $content);
        $this->assertStringContainsString('{{ $user->name }}', $content);
        $this->assertStringContainsString('{{ $user->email }}', $content);
        $this->assertStringContainsString('bg-gray-50', $content);
        $this->assertStringContainsString('text-gray-900', $content);
    }

    /** @test */
    public function it_can_generate_form_partial(): void
    {
        $content = $this->generator->generateView('form');

        $this->assertStringContainsString('<form', $content);
        $this->assertStringContainsString('method="POST"', $content);
        $this->assertStringContainsString('@csrf', $content);
        $this->assertStringContainsString('@method(\'PUT\')', $content);
        $this->assertStringContainsString('block', $content);
        $this->assertStringContainsString('text-sm', $content);
        $this->assertStringContainsString('font-medium', $content);
        $this->assertStringContainsString('text-gray-700', $content);
        $this->assertStringContainsString('border-gray-300', $content);
        $this->assertStringContainsString('rounded-md', $content);
        $this->assertStringContainsString('focus:ring-indigo-500', $content);
        $this->assertStringContainsString('focus:border-indigo-500', $content);
    }

    /** @test */
    public function it_can_generate_table_partial(): void
    {
        $content = $this->generator->generateView('table');

        $this->assertStringContainsString('<table', $content);
        $this->assertStringContainsString('min-w-full', $content);
        $this->assertStringContainsString('divide-y', $content);
        $this->assertStringContainsString('divide-gray-200', $content);
        $this->assertStringContainsString('<thead', $content);
        $this->assertStringContainsString('bg-gray-50', $content);
        $this->assertStringContainsString('<tbody', $content);
        $this->assertStringContainsString('bg-white', $content);
        $this->assertStringContainsString('@foreach($users as $user)', $content);
    }

    /** @test */
    public function it_generates_correct_action_buttons(): void
    {
        $content = $this->generator->generateView('index');

        $this->assertStringContainsString('bg-blue-500', $content);
        $this->assertStringContainsString('hover:bg-blue-700', $content);
        $this->assertStringContainsString('text-white', $content);
        $this->assertStringContainsString('font-bold', $content);
        $this->assertStringContainsString('py-2', $content);
        $this->assertStringContainsString('px-4', $content);
        $this->assertStringContainsString('rounded', $content);
    }

    /** @test */
    public function it_generates_form_validation_display(): void
    {
        $content = $this->generator->generateView('form');

        $this->assertStringContainsString('@error(\'name\')', $content);
        $this->assertStringContainsString('text-red-600', $content);
        $this->assertStringContainsString('text-sm', $content);
        $this->assertStringContainsString('{{ $message }}', $content);
        $this->assertStringContainsString('@enderror', $content);
    }

    /** @test */
    public function it_can_generate_with_datatable_support(): void
    {
        $generator = new TailwindGenerator(
            modelInstance: new User(),
            modelName: 'User',
            modelBaseName: 'User',
            routeName: 'users',
            layout: 'app',
            withDatatable: true,
            withSearch: false,
            withModals: false
        );

        $content = $generator->generateView('index');

        $this->assertStringContainsString('id="users-table"', $content);
        $this->assertStringContainsString('data-table', $content);
        $this->assertStringContainsString('$(document).ready', $content);
        $this->assertStringContainsString('DataTable', $content);
    }

    /** @test */
    public function it_can_generate_with_search_functionality(): void
    {
        $generator = new TailwindGenerator(
            modelInstance: new User(),
            modelName: 'User',
            modelBaseName: 'User',
            routeName: 'users',
            layout: 'app',
            withDatatable: false,
            withSearch: true,
            withModals: false
        );

        $filtersContent = $generator->generateView('filters');

        $this->assertStringContainsString('search-form', $filtersContent);
        $this->assertStringContainsString('type="search"', $filtersContent);
        $this->assertStringContainsString('placeholder="Search', $filtersContent);
        $this->assertStringContainsString('bg-white', $filtersContent);
        $this->assertStringContainsString('border-gray-300', $filtersContent);
        $this->assertStringContainsString('rounded-md', $filtersContent);
    }

    /** @test */
    public function it_can_generate_with_modal_support(): void
    {
        $generator = new TailwindGenerator(
            modelInstance: new User(),
            modelName: 'User',
            modelBaseName: 'User',
            routeName: 'users',
            layout: 'app',
            withDatatable: false,
            withSearch: false,
            withModals: true
        );

        $content = $generator->generateView('index');

        $this->assertStringContainsString('modal', $content);
        $this->assertStringContainsString('fixed inset-0', $content);
        $this->assertStringContainsString('bg-gray-600', $content);
        $this->assertStringContainsString('bg-opacity-50', $content);
        $this->assertStringContainsString('z-50', $content);
        $this->assertStringContainsString('transform transition-all', $content);
    }

    /** @test */
    public function it_generates_responsive_design_classes(): void
    {
        $content = $this->generator->generateView('index');

        $this->assertStringContainsString('sm:', $content);
        $this->assertStringContainsString('md:', $content);
        $this->assertStringContainsString('lg:', $content);
        $this->assertStringContainsString('xl:', $content);
    }

    /** @test */
    public function it_generates_proper_grid_layout(): void
    {
        $content = $this->generator->generateView('form');

        $this->assertStringContainsString('grid', $content);
        $this->assertStringContainsString('gap-6', $content);
        $this->assertStringContainsString('grid-cols-1', $content);
        $this->assertStringContainsString('sm:grid-cols-2', $content);
    }

    /** @test */
    public function it_includes_accessibility_attributes(): void
    {
        $content = $this->generator->generateView('form');

        $this->assertStringContainsString('aria-label', $content);
        $this->assertStringContainsString('role=', $content);
        $this->assertStringContainsString('aria-describedby', $content);
    }

    /** @test */
    public function it_generates_proper_navigation_breadcrumbs(): void
    {
        $content = $this->generator->generateView('show');

        $this->assertStringContainsString('breadcrumb', $content);
        $this->assertStringContainsString('text-gray-500', $content);
        $this->assertStringContainsString('hover:text-gray-700', $content);
        $this->assertStringContainsString('/', $content);
    }

    /** @test */
    public function it_handles_custom_layout(): void
    {
        $generator = new TailwindGenerator(
            modelInstance: new User(),
            modelName: 'User',
            modelBaseName: 'User',
            routeName: 'users',
            layout: 'layouts.admin',
            withDatatable: false,
            withSearch: false,
            withModals: false
        );

        $content = $generator->generateView('index');

        $this->assertStringContainsString('@extends(\'layouts.admin\')', $content);
    }

    /** @test */
    public function it_generates_alert_components(): void
    {
        $content = $this->generator->generateView('index');

        $this->assertStringContainsString('@if(session(\'success\'))', $content);
        $this->assertStringContainsString('bg-green-100', $content);
        $this->assertStringContainsString('border-green-400', $content);
        $this->assertStringContainsString('text-green-700', $content);
        $this->assertStringContainsString('{{ session(\'success\') }}', $content);
    }

    /** @test */
    public function it_generates_loading_states(): void
    {
        $generator = new TailwindGenerator(
            modelInstance: new User(),
            modelName: 'User',
            modelBaseName: 'User',
            routeName: 'users',
            layout: 'app',
            withDatatable: true,
            withSearch: false,
            withModals: false
        );

        $content = $generator->generateView('index');

        $this->assertStringContainsString('loading', $content);
        $this->assertStringContainsString('spinner', $content);
        $this->assertStringContainsString('animate-spin', $content);
    }

    /** @test */
    public function it_generates_proper_form_field_types(): void
    {
        $user = new User();
        $user->fillable(['name', 'email', 'password', 'bio', 'is_active', 'birth_date']);
        
        $generator = new TailwindGenerator(
            modelInstance: $user,
            modelName: 'User',
            modelBaseName: 'User',
            routeName: 'users',
            layout: 'app',
            withDatatable: false,
            withSearch: false,
            withModals: false
        );

        $content = $generator->generateView('form');

        $this->assertStringContainsString('type="text"', $content);
        $this->assertStringContainsString('type="email"', $content);
        $this->assertStringContainsString('type="password"', $content);
        $this->assertStringContainsString('<textarea', $content);
        $this->assertStringContainsString('type="checkbox"', $content);
        $this->assertStringContainsString('type="date"', $content);
    }
}