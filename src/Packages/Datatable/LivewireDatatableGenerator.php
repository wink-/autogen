<?php

declare(strict_types=1);

namespace AutoGen\Packages\Datatable;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class LivewireDatatableGenerator
{
    protected string $modelName;
    protected array $options;
    protected array $generatedFiles = [];

    public function __construct(string $modelName, array $options = [])
    {
        $this->modelName = $modelName;
        $this->options = $options;
    }

    /**
     * Generate Livewire DataTable files.
     */
    public function generate(): array
    {
        $this->generateLivewireComponent();
        $this->generateLivewireView();
        
        return $this->generatedFiles;
    }

    /**
     * Generate the Livewire component class.
     */
    protected function generateLivewireComponent(): void
    {
        $path = $this->getLivewireComponentPath();
        $this->ensureDirectoryExists(dirname($path));

        $stub = $this->getComponentStub();
        $content = $this->replaceStubVariables($stub);

        if (File::exists($path) && !($this->options['force'] ?? false)) {
            throw new \Exception("Livewire component already exists at: {$path}");
        }

        File::put($path, $content);
        $this->generatedFiles[] = $path;
    }

    /**
     * Generate the Livewire view file.
     */
    protected function generateLivewireView(): void
    {
        $path = $this->getLivewireViewPath();
        $this->ensureDirectoryExists(dirname($path));

        $stub = $this->getViewStub();
        $content = $this->replaceStubVariables($stub);

        if (File::exists($path) && !($this->options['force'] ?? false)) {
            throw new \Exception("Livewire view already exists at: {$path}");
        }

        File::put($path, $content);
        $this->generatedFiles[] = $path;
    }

    /**
     * Get the component stub file.
     */
    protected function getComponentStub(): string
    {
        $customStubPath = config('autogen.custom_stubs_path');
        
        if ($customStubPath && File::exists($customStubPath . '/datatable/livewire.component.stub')) {
            return File::get($customStubPath . '/datatable/livewire.component.stub');
        }

        return File::get(__DIR__ . '/Stubs/livewire.component.stub');
    }

    /**
     * Get the view stub file.
     */
    protected function getViewStub(): string
    {
        $customStubPath = config('autogen.custom_stubs_path');
        
        if ($customStubPath && File::exists($customStubPath . '/datatable/livewire.view.stub')) {
            return File::get($customStubPath . '/datatable/livewire.view.stub');
        }

        return File::get(__DIR__ . '/Stubs/livewire.view.stub');
    }

    /**
     * Replace variables in the stub.
     */
    protected function replaceStubVariables(string $stub): string
    {
        $replacements = $this->getReplacements();

        foreach ($replacements as $key => $value) {
            $stub = str_replace('{{ ' . $key . ' }}', $value, $stub);
        }

        return $stub;
    }

    /**
     * Get all replacement variables.
     */
    protected function getReplacements(): array
    {
        $modelClass = $this->getModelClass();
        $modelNamespace = $this->getModelNamespace();
        $componentNamespace = $this->getLivewireComponentNamespace();
        $componentName = $this->getComponentName();
        $modelVariable = $this->getModelVariable();
        $modelVariablePlural = Str::plural($modelVariable);

        $replacements = [
            'namespace' => $componentNamespace,
            'modelNamespace' => $modelNamespace,
            'modelClass' => $modelClass,
            'componentName' => $componentName,
            'modelVariable' => $modelVariable,
            'modelVariablePlural' => $modelVariablePlural,
            'properties' => $this->getComponentProperties(),
            'queryMethod' => $this->getQueryMethod(),
            'searchMethods' => $this->getSearchMethods(),
            'bulkMethods' => $this->getBulkMethods(),
            'exportMethods' => $this->getExportMethods(),
            'cacheImplementation' => $this->getCacheImplementation(),
            'virtualScrollImplementation' => $this->getVirtualScrollImplementation(),
            'tableHeaders' => $this->getTableHeaders(),
            'tableRows' => $this->getTableRows(),
            'searchInputs' => $this->getSearchInputs(),
            'bulkActions' => $this->getBulkActionButtons(),
            'exportButtons' => $this->getExportButtons(),
            'paginationLinks' => $this->getPaginationLinks(),
        ];

        return $replacements;
    }

    /**
     * Get Livewire component properties.
     */
    protected function getComponentProperties(): string
    {
        $properties = [
            'public $perPage = 15;',
            'public $search = "";',
            'public $sortField = "id";',
            'public $sortDirection = "asc";',
        ];

        if ($this->options['withSearch'] ?? false) {
            $properties[] = 'public $searchName = "";';
            $properties[] = 'public $searchEmail = "";';
            $properties[] = 'public $dateFrom = "";';
            $properties[] = 'public $dateTo = "";';
            $properties[] = 'public $showAdvancedSearch = false;';
        }

        if ($this->options['withBulk'] ?? false) {
            $properties[] = 'public $selectedItems = [];';
            $properties[] = 'public $selectAll = false;';
        }

        if ($this->options['virtualScroll'] ?? false) {
            $properties[] = 'public $loadedCount = 50;';
            $properties[] = 'public $loadIncrement = 25;';
        }

        return '    ' . implode("\n    ", $properties);
    }

    /**
     * Get the main query method.
     */
    protected function getQueryMethod(): string
    {
        $modelClass = $this->getModelClass();
        $query = "
    public function getRowsProperty()
    {
        \$query = {$modelClass}::query();

        // Global search
        if (\$this->search) {
            \$query->where(function (\$q) {
                \$q->where('name', 'like', '%' . \$this->search . '%')
                  ->orWhere('email', 'like', '%' . \$this->search . '%');
            });
        }

        {$this->getAdvancedSearchQuery()}

        // Apply sorting
        \$query->orderBy(\$this->sortField, \$this->sortDirection);

        {$this->getQueryOptimizations()}

        {$this->getCacheQuery()}

        {$this->getVirtualScrollQuery()}

        return \$query->paginate(\$this->perPage);
    }";

        return $query;
    }

    /**
     * Get advanced search query.
     */
    protected function getAdvancedSearchQuery(): string
    {
        if (!($this->options['withSearch'] ?? false)) {
            return '';
        }

        return "
        // Advanced search filters
        if (\$this->searchName) {
            \$query->where('name', 'like', '%' . \$this->searchName . '%');
        }

        if (\$this->searchEmail) {
            \$query->where('email', 'like', '%' . \$this->searchEmail . '%');
        }

        if (\$this->dateFrom) {
            \$query->whereDate('created_at', '>=', \$this->dateFrom);
        }

        if (\$this->dateTo) {
            \$query->whereDate('created_at', '<=', \$this->dateTo);
        }";
    }

    /**
     * Get query optimizations.
     */
    protected function getQueryOptimizations(): string
    {
        $optimizations = [];

        // Select specific columns
        $optimizations[] = "\$query->select(['id', 'name', 'email', 'created_at', 'updated_at']);";

        // Cursor pagination if enabled
        if ($this->options['cursorPagination'] ?? false) {
            $optimizations[] = "
        // Use cursor pagination for better performance on large datasets
        if (\$this->sortField === 'id') {
            \$query->cursorPaginate(\$this->perPage);
        }";
        }

        return $optimizations ? '        ' . implode("\n        ", $optimizations) : '';
    }

    /**
     * Get cache query implementation.
     */
    protected function getCacheQuery(): string
    {
        if (!($this->options['cache'] ?? false)) {
            return '';
        }

        return "
        // Cache implementation
        \$cacheKey = 'livewire-datatable:' . md5(serialize([
            \$this->search, \$this->searchName, \$this->searchEmail,
            \$this->dateFrom, \$this->dateTo, \$this->sortField,
            \$this->sortDirection, \$this->perPage, request('page', 1)
        ]));

        return cache()->remember(\$cacheKey, 300, function () use (\$query) {
            return \$query->paginate(\$this->perPage);
        });";
    }

    /**
     * Get virtual scroll query implementation.
     */
    protected function getVirtualScrollQuery(): string
    {
        if (!($this->options['virtualScroll'] ?? false)) {
            return '';
        }

        return "
        // Virtual scrolling implementation
        if (\$this->loadedCount > 0) {
            \$query->limit(\$this->loadedCount);
        }";
    }

    /**
     * Get search methods.
     */
    protected function getSearchMethods(): string
    {
        if (!($this->options['withSearch'] ?? false)) {
            return '';
        }

        return "
    public function toggleAdvancedSearch()
    {
        \$this->showAdvancedSearch = !\$this->showAdvancedSearch;
    }

    public function clearSearch()
    {
        \$this->search = '';
        \$this->searchName = '';
        \$this->searchEmail = '';
        \$this->dateFrom = '';
        \$this->dateTo = '';
        \$this->showAdvancedSearch = false;
        {$this->getClearCacheCall()}
    }";
    }

    /**
     * Get bulk action methods.
     */
    protected function getBulkMethods(): string
    {
        if (!($this->options['withBulk'] ?? false)) {
            return '';
        }

        $modelClass = $this->getModelClass();

        return "
    public function updatedSelectAll(\$value)
    {
        if (\$value) {
            \$this->selectedItems = \$this->rows->pluck('id')->toArray();
        } else {
            \$this->selectedItems = [];
        }
    }

    public function bulkDelete()
    {
        if (empty(\$this->selectedItems)) {
            \$this->addError('bulk', 'No items selected');
            return;
        }

        {$modelClass}::whereIn('id', \$this->selectedItems)->delete();
        \$this->selectedItems = [];
        \$this->selectAll = false;
        
        {$this->getClearCacheCall()}
        
        \$this->emit('itemsDeleted', count(\$this->selectedItems));
        session()->flash('success', 'Selected items deleted successfully');
    }

    public function bulkStatusUpdate(\$status)
    {
        if (empty(\$this->selectedItems)) {
            \$this->addError('bulk', 'No items selected');
            return;
        }

        {$modelClass}::whereIn('id', \$this->selectedItems)->update(['status' => \$status]);
        \$this->selectedItems = [];
        \$this->selectAll = false;
        
        {$this->getClearCacheCall()}
        
        session()->flash('success', 'Selected items updated successfully');
    }";
    }

    /**
     * Get export methods.
     */
    protected function getExportMethods(): string
    {
        if (!($this->options['withExports'] ?? false)) {
            return '';
        }

        return "
    public function exportExcel()
    {
        return \$this->export('excel');
    }

    public function exportCsv()
    {
        return \$this->export('csv');
    }

    public function exportPdf()
    {
        return \$this->export('pdf');
    }

    protected function export(\$format)
    {
        \$query = {$this->getModelClass()}::query();
        
        // Apply same filters as the main query
        if (\$this->search) {
            \$query->where(function (\$q) {
                \$q->where('name', 'like', '%' . \$this->search . '%')
                  ->orWhere('email', 'like', '%' . \$this->search . '%');
            });
        }

        {$this->getAdvancedSearchQuery()}

        \$data = \$query->get();
        
        " . ($this->options['backgroundJobs'] ? "
        // Process export in background job for large datasets
        if (\$data->count() > 1000) {
            dispatch(new Export{$this->getModelClass()}Job(\$data, \$format, auth()->user()));
            session()->flash('info', 'Export job started. You will receive an email when ready.');
            return;
        }" : "") . "

        return (new {$this->getModelClass()}Export(\$data))->download('{$this->getModelVariable()}s.' . \$format);
    }";
    }

    /**
     * Get cache implementation.
     */
    protected function getCacheImplementation(): string
    {
        if (!($this->options['cache'] ?? false)) {
            return '';
        }

        return "
    /**
     * Clear cache when data changes.
     */
    protected function clearCache()
    {
        cache()->tags(['livewire-datatable'])->flush();
    }";
    }

    /**
     * Get clear cache method call.
     */
    protected function getClearCacheCall(): string
    {
        return ($this->options['cache'] ?? false) ? '$this->clearCache();' : '';
    }

    /**
     * Get virtual scroll implementation.
     */
    protected function getVirtualScrollImplementation(): string
    {
        if (!($this->options['virtualScroll'] ?? false)) {
            return '';
        }

        return "
    public function loadMore()
    {
        \$this->loadedCount += \$this->loadIncrement;
    }

    public function getHasMoreProperty()
    {
        return \$this->rows->count() >= \$this->loadedCount;
    }";
    }

    /**
     * Get table headers for the view.
     */
    protected function getTableHeaders(): string
    {
        $headers = [];

        if ($this->options['withBulk'] ?? false) {
            $headers[] = '<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    <input type="checkbox" wire:model="selectAll" class="rounded">
                </th>';
        }

        $headers[] = '<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer" wire:click="sortBy(\'id\')">
                ID
                @if($sortField === \'id\')
                    @if($sortDirection === \'asc\') ↑ @else ↓ @endif
                @endif
            </th>';

        $headers[] = '<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer" wire:click="sortBy(\'name\')">
                Name
                @if($sortField === \'name\')
                    @if($sortDirection === \'asc\') ↑ @else ↓ @endif
                @endif
            </th>';

        $headers[] = '<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer" wire:click="sortBy(\'email\')">
                Email
                @if($sortField === \'email\')
                    @if($sortDirection === \'asc\') ↑ @else ↓ @endif
                @endif
            </th>';

        $headers[] = '<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer" wire:click="sortBy(\'created_at\')">
                Created At
                @if($sortField === \'created_at\')
                    @if($sortDirection === \'asc\') ↑ @else ↓ @endif
                @endif
            </th>';

        $headers[] = '<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Actions
            </th>';

        return implode("\n            ", $headers);
    }

    /**
     * Get table rows for the view.
     */
    protected function getTableRows(): string
    {
        $modelVariable = $this->getModelVariable();
        $cells = [];

        if ($this->options['withBulk'] ?? false) {
            $cells[] = '<td class="px-6 py-4 whitespace-nowrap">
                        <input type="checkbox" wire:model="selectedItems" value="{{ $' . $modelVariable . '->id }}" class="rounded">
                    </td>';
        }

        $cells[] = '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {{ $' . $modelVariable . '->id }}
                    </td>';

        $cells[] = '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {{ $' . $modelVariable . '->name }}
                    </td>';

        $cells[] = '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {{ $' . $modelVariable . '->email }}
                    </td>';

        $cells[] = '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        {{ $' . $modelVariable . '->created_at->format(\'Y-m-d H:i:s\') }}
                    </td>';

        $cells[] = '<td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                        <a href="#" class="text-indigo-600 hover:text-indigo-900 mr-3">Edit</a>
                        <a href="#" class="text-red-600 hover:text-red-900" 
                           onclick="confirm(\'Are you sure?\') || event.stopImmediatePropagation()"
                           wire:click="delete({{ $' . $modelVariable . '->id }})">Delete</a>
                    </td>';

        return implode("\n                    ", $cells);
    }

    /**
     * Get search inputs for the view.
     */
    protected function getSearchInputs(): string
    {
        if (!($this->options['withSearch'] ?? false)) {
            return '';
        }

        return '
            @if($showAdvancedSearch)
                <div class="bg-gray-50 p-4 rounded-lg mb-4 space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Name</label>
                            <input type="text" wire:model.debounce.300ms="searchName" 
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Email</label>
                            <input type="text" wire:model.debounce.300ms="searchEmail" 
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Date From</label>
                            <input type="date" wire:model="dateFrom" 
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Date To</label>
                            <input type="date" wire:model="dateTo" 
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        </div>
                    </div>
                    <div class="flex justify-end">
                        <button wire:click="clearSearch" 
                                class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                            Clear Filters
                        </button>
                    </div>
                </div>
            @endif';
    }

    /**
     * Get bulk action buttons for the view.
     */
    protected function getBulkActionButtons(): string
    {
        if (!($this->options['withBulk'] ?? false)) {
            return '';
        }

        return '
            @if(count($selectedItems) > 0)
                <div class="bg-blue-50 p-4 rounded-lg mb-4">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-blue-800">{{ count($selectedItems) }} items selected</span>
                        <div class="space-x-2">
                            <button wire:click="bulkStatusUpdate(\'active\')" 
                                    class="bg-green-500 hover:bg-green-700 text-white font-bold py-1 px-3 rounded text-sm">
                                Activate
                            </button>
                            <button wire:click="bulkStatusUpdate(\'inactive\')" 
                                    class="bg-yellow-500 hover:bg-yellow-700 text-white font-bold py-1 px-3 rounded text-sm">
                                Deactivate
                            </button>
                            <button wire:click="bulkDelete" 
                                    class="bg-red-500 hover:bg-red-700 text-white font-bold py-1 px-3 rounded text-sm"
                                    onclick="return confirm(\'Are you sure you want to delete selected items?\')">
                                Delete
                            </button>
                        </div>
                    </div>
                </div>
            @endif';
    }

    /**
     * Get export buttons for the view.
     */
    protected function getExportButtons(): string
    {
        if (!($this->options['withExports'] ?? false)) {
            return '';
        }

        return '
                <div class="space-x-2">
                    <button wire:click="exportExcel" 
                            class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                        Export Excel
                    </button>
                    <button wire:click="exportCsv" 
                            class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                        Export CSV
                    </button>
                    <button wire:click="exportPdf" 
                            class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded">
                        Export PDF
                    </button>
                </div>';
    }

    /**
     * Get pagination links for the view.
     */
    protected function getPaginationLinks(): string
    {
        if ($this->options['virtualScroll'] ?? false) {
            return '
            @if($hasMore)
                <div class="text-center mt-4">
                    <button wire:click="loadMore" 
                            class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                        Load More
                    </button>
                </div>
            @endif';
        }

        return '{{ $rows->links() }}';
    }

    /**
     * Get the model class name.
     */
    protected function getModelClass(): string
    {
        return class_basename($this->modelName);
    }

    /**
     * Get the model namespace.
     */
    protected function getModelNamespace(): string
    {
        $modelPath = str_replace('/', '\\', $this->modelName);
        $namespace = 'App\\Models';

        if (str_contains($modelPath, '\\')) {
            $parts = explode('\\', $modelPath);
            array_pop($parts);
            $namespace .= '\\' . implode('\\', $parts);
        }

        return $namespace;
    }

    /**
     * Get the Livewire component namespace.
     */
    protected function getLivewireComponentNamespace(): string
    {
        $namespace = 'App\\Http\\Livewire';

        $modelPath = str_replace('/', '\\', $this->modelName);
        if (str_contains($modelPath, '\\')) {
            $parts = explode('\\', $modelPath);
            array_pop($parts);
            $namespace .= '\\' . implode('\\', $parts);
        }

        return $namespace;
    }

    /**
     * Get the component name.
     */
    protected function getComponentName(): string
    {
        return $this->getModelClass() . 'Datatable';
    }

    /**
     * Get the model variable name.
     */
    protected function getModelVariable(): string
    {
        return Str::camel($this->getModelClass());
    }

    /**
     * Get the Livewire component file path.
     */
    protected function getLivewireComponentPath(): string
    {
        $path = app_path('Http/Livewire');

        $modelPath = str_replace('\\', '/', $this->modelName);
        if (str_contains($modelPath, '/')) {
            $parts = explode('/', $modelPath);
            array_pop($parts);
            $path .= '/' . implode('/', $parts);
        }

        return $path . '/' . $this->getComponentName() . '.php';
    }

    /**
     * Get the Livewire view file path.
     */
    protected function getLivewireViewPath(): string
    {
        $parts = explode('/', $this->modelName);
        $paths = array_map(fn($part) => Str::kebab($part), $parts);
        
        $viewPath = resource_path('views/livewire/' . implode('/', $paths));
        
        return $viewPath . '-datatable.blade.php';
    }

    /**
     * Ensure directory exists.
     */
    protected function ensureDirectoryExists(string $directory): void
    {
        if (!File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }
    }
}