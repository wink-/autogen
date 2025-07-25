<?php

declare(strict_types=1);

namespace AutoGen\Packages\Datatable;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class InertiaDatatableGenerator
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
     * Generate Inertia.js DataTable files.
     */
    public function generate(): array
    {
        $this->generateInertiaController();
        $this->generateVueComponent();
        $this->generateComposables();
        
        return $this->generatedFiles;
    }

    /**
     * Generate the Inertia controller methods.
     */
    protected function generateInertiaController(): void
    {
        $path = $this->getControllerPath();
        $this->ensureDirectoryExists(dirname($path));

        $stub = $this->getControllerStub();
        $content = $this->replaceStubVariables($stub);

        if (File::exists($path) && !($this->options['force'] ?? false)) {
            throw new \Exception("Inertia controller already exists at: {$path}");
        }

        File::put($path, $content);
        $this->generatedFiles[] = $path;
    }

    /**
     * Generate the Vue component.
     */
    protected function generateVueComponent(): void
    {
        $path = $this->getVueComponentPath();
        $this->ensureDirectoryExists(dirname($path));

        $stub = $this->getVueStub();
        $content = $this->replaceStubVariables($stub);

        if (File::exists($path) && !($this->options['force'] ?? false)) {
            throw new \Exception("Vue component already exists at: {$path}");
        }

        File::put($path, $content);
        $this->generatedFiles[] = $path;
    }

    /**
     * Generate Vue composables for data fetching and state management.
     */
    protected function generateComposables(): void
    {
        $path = $this->getComposablePath();
        $this->ensureDirectoryExists(dirname($path));

        $stub = $this->getComposableStub();
        $content = $this->replaceStubVariables($stub);

        if (File::exists($path) && !($this->options['force'] ?? false)) {
            throw new \Exception("Composable already exists at: {$path}");
        }

        File::put($path, $content);
        $this->generatedFiles[] = $path;
    }

    /**
     * Get the controller stub file.
     */
    protected function getControllerStub(): string
    {
        $customStubPath = config('autogen.custom_stubs_path');
        
        if ($customStubPath && File::exists($customStubPath . '/datatable/inertia.controller.stub')) {
            return File::get($customStubPath . '/datatable/inertia.controller.stub');
        }

        return File::get(__DIR__ . '/Stubs/inertia.controller.stub');
    }

    /**
     * Get the Vue component stub file.
     */
    protected function getVueStub(): string
    {
        $customStubPath = config('autogen.custom_stubs_path');
        
        if ($customStubPath && File::exists($customStubPath . '/datatable/inertia.vue.stub')) {
            return File::get($customStubPath . '/datatable/inertia.vue.stub');
        }

        return File::get(__DIR__ . '/Stubs/inertia.vue.stub');
    }

    /**
     * Get the composable stub file.
     */
    protected function getComposableStub(): string
    {
        $customStubPath = config('autogen.custom_stubs_path');
        
        if ($customStubPath && File::exists($customStubPath . '/datatable/inertia.composable.stub')) {
            return File::get($customStubPath . '/datatable/inertia.composable.stub');
        }

        return File::get(__DIR__ . '/Stubs/inertia.composable.stub');
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
        $controllerNamespace = $this->getControllerNamespace();
        $controllerName = $this->getControllerName();
        $modelVariable = $this->getModelVariable();
        $modelVariablePlural = Str::plural($modelVariable);
        $routeName = $this->getRouteName();

        $replacements = [
            'namespace' => $controllerNamespace,
            'modelNamespace' => $modelNamespace,
            'modelClass' => $modelClass,
            'controllerName' => $controllerName,
            'modelVariable' => $modelVariable,
            'modelVariablePlural' => $modelVariablePlural,
            'routeName' => $routeName,
            'componentName' => $this->getComponentName(),
            'composableName' => $this->getComposableName(),
            'controllerMethods' => $this->getControllerMethods(),
            'queryOptimizations' => $this->getQueryOptimizations(),
            'cacheImplementation' => $this->getCacheImplementation(),
            'exportMethods' => $this->getExportMethods(),
            'bulkMethods' => $this->getBulkMethods(),
            'vueTemplate' => $this->getVueTemplate(),
            'vueScript' => $this->getVueScript(),
            'vueComposable' => $this->getVueComposable(),
        ];

        return $replacements;
    }

    /**
     * Get controller methods implementation.
     */
    protected function getControllerMethods(): string
    {
        $modelClass = $this->getModelClass();
        $modelVariable = $this->getModelVariable();
        $modelVariablePlural = Str::plural($modelVariable);

        return "
    /**
     * Display a listing of the resource.
     */
    public function index(Request \$request)
    {
        return Inertia::render('{$this->getComponentName()}', [
            '{$modelVariablePlural}' => \$this->getData(\$request),
            'filters' => \$request->only(['search', 'sort', 'direction', 'per_page']),
        ]);
    }

    /**
     * Get paginated data for the datatable.
     */
    public function data(Request \$request)
    {
        return response()->json(\$this->getData(\$request));
    }

    /**
     * Get the data with filters and pagination.
     */
    protected function getData(Request \$request)
    {
        \$query = {$modelClass}::query();

        {$this->getSearchImplementation()}

        {$this->getSortingImplementation()}

        {$this->getQueryOptimizations()}

        {$this->getCacheQuery()}

        \$perPage = \$request->get('per_page', 15);
        
        {$this->getCursorPaginationImplementation()}

        return \$query->paginate(\$perPage)->withQueryString();
    }

    {$this->getExportMethodsImplementation()}

    {$this->getBulkMethodsImplementation()}";
    }

    /**
     * Get search implementation.
     */
    protected function getSearchImplementation(): string
    {
        $implementation = "
        // Global search
        if (\$request->filled('search')) {
            \$search = \$request->get('search');
            \$query->where(function (\$q) use (\$search) {
                \$q->where('name', 'like', \"%{\$search}%\")
                  ->orWhere('email', 'like', \"%{\$search}%\");
            });
        }";

        if ($this->options['withSearch'] ?? false) {
            $implementation .= "

        // Advanced search filters
        if (\$request->filled('name')) {
            \$query->where('name', 'like', '%' . \$request->get('name') . '%');
        }

        if (\$request->filled('email')) {
            \$query->where('email', 'like', '%' . \$request->get('email') . '%');
        }

        if (\$request->filled('date_from')) {
            \$query->whereDate('created_at', '>=', \$request->get('date_from'));
        }

        if (\$request->filled('date_to')) {
            \$query->whereDate('created_at', '<=', \$request->get('date_to'));
        }";
        }

        return $implementation;
    }

    /**
     * Get sorting implementation.
     */
    protected function getSortingImplementation(): string
    {
        return "
        // Sorting
        \$sortField = \$request->get('sort', 'id');
        \$sortDirection = \$request->get('direction', 'asc');
        
        \$allowedSorts = ['id', 'name', 'email', 'created_at', 'updated_at'];
        if (in_array(\$sortField, \$allowedSorts)) {
            \$query->orderBy(\$sortField, \$sortDirection);
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

        // Add database-specific optimizations
        $optimizations[] = "
        // Database-specific optimizations
        \$query->when(config('database.default') === 'mysql', function (\$q) {
            \$q->addSelect(DB::raw('SQL_CALC_FOUND_ROWS *'));
        });";

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
        \$cacheKey = 'inertia-datatable:' . md5(serialize(\$request->all()));
        \$cacheDuration = config('datatables.cache.duration', 300);
        
        return Cache::remember(\$cacheKey, \$cacheDuration, function () use (\$query, \$perPage) {
            return \$query->paginate(\$perPage)->withQueryString();
        });";
    }

    /**
     * Get cursor pagination implementation.
     */
    protected function getCursorPaginationImplementation(): string
    {
        if (!($this->options['cursorPagination'] ?? false)) {
            return '';
        }

        return "
        // Use cursor pagination for better performance on large datasets
        if (\$request->get('use_cursor', false) && \$request->get('sort') === 'id') {
            return \$query->cursorPaginate(\$perPage);
        }";
    }

    /**
     * Get export methods implementation.
     */
    protected function getExportMethodsImplementation(): string
    {
        if (!($this->options['withExports'] ?? false)) {
            return '';
        }

        $modelClass = $this->getModelClass();

        return "
    /**
     * Export data to Excel.
     */
    public function exportExcel(Request \$request)
    {
        return \$this->export(\$request, 'excel');
    }

    /**
     * Export data to CSV.
     */
    public function exportCsv(Request \$request)
    {
        return \$this->export(\$request, 'csv');
    }

    /**
     * Export data to PDF.
     */
    public function exportPdf(Request \$request)
    {
        return \$this->export(\$request, 'pdf');
    }

    /**
     * Handle export functionality.
     */
    protected function export(Request \$request, string \$format)
    {
        \$query = {$modelClass}::query();
        
        {$this->getSearchImplementation()}
        {$this->getSortingImplementation()}
        
        \$data = \$query->get();
        
        " . ($this->options['backgroundJobs'] ? "
        // Process export in background job for large datasets
        if (\$data->count() > 1000) {
            dispatch(new Export{$modelClass}Job(\$data, \$format, auth()->user()));
            return response()->json(['message' => 'Export job started. You will receive an email when ready.']);
        }" : "") . "

        return (new {$modelClass}Export(\$data))->download('{$this->getModelVariable()}s.' . \$format);
    }";
    }

    /**
     * Get bulk methods implementation.
     */
    protected function getBulkMethodsImplementation(): string
    {
        if (!($this->options['withBulk'] ?? false)) {
            return '';
        }

        $modelClass = $this->getModelClass();

        return "
    /**
     * Handle bulk actions.
     */
    public function bulkAction(Request \$request)
    {
        \$request->validate([
            'action' => 'required|string|in:delete,activate,deactivate',
            'ids' => 'required|array|min:1',
            'ids.*' => 'required|integer|exists:{$this->getTableName()},id'
        ]);

        \$action = \$request->get('action');
        \$ids = \$request->get('ids');

        switch (\$action) {
            case 'delete':
                {$modelClass}::whereIn('id', \$ids)->delete();
                \$message = 'Selected items deleted successfully';
                break;
                
            case 'activate':
                {$modelClass}::whereIn('id', \$ids)->update(['status' => 'active']);
                \$message = 'Selected items activated successfully';
                break;
                
            case 'deactivate':
                {$modelClass}::whereIn('id', \$ids)->update(['status' => 'inactive']);
                \$message = 'Selected items deactivated successfully';
                break;
        }

        {$this->getClearCacheCall()}

        return response()->json(['message' => \$message]);
    }";
    }

    /**
     * Get Vue template.
     */
    protected function getVueTemplate(): string
    {
        $modelVariable = $this->getModelVariable();
        $modelVariablePlural = Str::plural($modelVariable);

        return '<template>
  <div class="bg-white shadow overflow-hidden sm:rounded-md">
    <!-- Header with search and controls -->
    <div class="px-4 py-5 sm:p-6">
      <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-4">
        <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4 sm:mb-0">
          ' . Str::title($modelVariablePlural) . '
        </h3>
        
        <div class="flex flex-col sm:flex-row space-y-2 sm:space-y-0 sm:space-x-2">
          ' . ($this->options['withSearch'] ? '
          <button 
            @click="toggleAdvancedSearch"
            class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
          >
            Advanced Search
          </button>' : '') . '
          
          ' . ($this->options['withExports'] ? '
          <div class="relative inline-block text-left" v-if="' . $modelVariablePlural . '.data.length > 0">
            <button 
              @click="showExportMenu = !showExportMenu"
              class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700"
            >
              Export
            </button>
            <div v-show="showExportMenu" class="absolute right-0 mt-2 w-48 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 z-10">
              <div class="py-1">
                <button @click="exportData(\'excel\')" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 w-full text-left">
                  Export to Excel
                </button>
                <button @click="exportData(\'csv\')" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 w-full text-left">
                  Export to CSV
                </button>
                <button @click="exportData(\'pdf\')" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 w-full text-left">
                  Export to PDF
                </button>
              </div>
            </div>
          </div>' : '') . '
        </div>
      </div>

      <!-- Search Input -->
      <div class="mb-4">
        <input
          v-model="filters.search"
          @input="debouncedSearch"
          type="text"
          placeholder="Search..."
          class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
        />
      </div>

      ' . ($this->options['withSearch'] ? '
      <!-- Advanced Search -->
      <div v-show="showAdvancedSearch" class="bg-gray-50 p-4 rounded-lg mb-4">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700">Name</label>
            <input
              v-model="filters.name"
              @input="debouncedSearch"
              type="text"
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Email</label>
            <input
              v-model="filters.email"
              @input="debouncedSearch"
              type="text"
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Date From</label>
            <input
              v-model="filters.date_from"
              @change="search"
              type="date"
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Date To</label>
            <input
              v-model="filters.date_to"
              @change="search"
              type="date"
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
            />
          </div>
        </div>
        <div class="mt-4 flex justify-end">
          <button @click="clearFilters" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
            Clear Filters
          </button>
        </div>
      </div>' : '') . '

      ' . ($this->options['withBulk'] ? '
      <!-- Bulk Actions -->
      <div v-show="selectedItems.length > 0" class="bg-blue-50 p-4 rounded-lg mb-4">
        <div class="flex items-center justify-between">
          <span class="text-sm text-blue-800">{{ selectedItems.length }} items selected</span>
          <div class="space-x-2">
            <button @click="bulkAction(\'activate\')" class="bg-green-500 hover:bg-green-700 text-white font-bold py-1 px-3 rounded text-sm">
              Activate
            </button>
            <button @click="bulkAction(\'deactivate\')" class="bg-yellow-500 hover:bg-yellow-700 text-white font-bold py-1 px-3 rounded text-sm">
              Deactivate
            </button>
            <button @click="bulkAction(\'delete\')" class="bg-red-500 hover:bg-red-700 text-white font-bold py-1 px-3 rounded text-sm">
              Delete
            </button>
          </div>
        </div>
      </div>' : '') . '

      <!-- Data Table -->
      <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
        <table class="min-w-full divide-y divide-gray-300">
          <thead class="bg-gray-50">
            <tr>
              ' . ($this->options['withBulk'] ? '
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                <input type="checkbox" v-model="selectAll" @change="toggleSelectAll" class="rounded">
              </th>' : '') . '
              <th 
                @click="sort(\'id\')"
                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100"
              >
                ID
                <span v-if="filters.sort === \'id\'">{{ filters.direction === \'asc\' ? \'↑\' : \'↓\' }}</span>
              </th>
              <th 
                @click="sort(\'name\')"
                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100"
              >
                Name
                <span v-if="filters.sort === \'name\'">{{ filters.direction === \'asc\' ? \'↑\' : \'↓\' }}</span>
              </th>
              <th 
                @click="sort(\'email\')"
                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100"
              >
                Email
                <span v-if="filters.sort === \'email\'">{{ filters.direction === \'asc\' ? \'↑\' : \'↓\' }}</span>
              </th>
              <th 
                @click="sort(\'created_at\')"
                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100"
              >
                Created At
                <span v-if="filters.sort === \'created_at\'">{{ filters.direction === \'asc\' ? \'↑\' : \'↓\' }}</span>
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Actions
              </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="' . $modelVariable . ' in ' . $modelVariablePlural . '.data" :key="' . $modelVariable . '.id">
              ' . ($this->options['withBulk'] ? '
              <td class="px-6 py-4 whitespace-nowrap">
                <input type="checkbox" v-model="selectedItems" :value="' . $modelVariable . '.id" class="rounded">
              </td>' : '') . '
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                {{ ' . $modelVariable . '.id }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                {{ ' . $modelVariable . '.name }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                {{ ' . $modelVariable . '.email }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                {{ formatDate(' . $modelVariable . '.created_at) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                <a href="#" class="text-indigo-600 hover:text-indigo-900 mr-3">Edit</a>
                <a href="#" @click.prevent="deleteItem(' . $modelVariable . '.id)" class="text-red-600 hover:text-red-900">Delete</a>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div class="mt-4">
        <Pagination :links="' . $modelVariablePlural . '.links" />
      </div>
    </div>
  </div>
</template>';
    }

    /**
     * Get Vue script section.
     */
    protected function getVueScript(): string
    {
        $modelVariable = $this->getModelVariable();
        $modelVariablePlural = Str::plural($modelVariable);
        $composableName = $this->getComposableName();

        return '<script setup>
import { ref, computed, onMounted, watch } from \'vue\'
import { router } from \'@inertiajs/vue3\'
import { debounce } from \'lodash\'
import Pagination from \'@/Components/Pagination.vue\'
import { ' . $composableName . ' } from \'@/Composables/' . $composableName . '\'

const props = defineProps({
  ' . $modelVariablePlural . ': Object,
  filters: Object
})

const {
  ' . $modelVariablePlural . ',
  filters,
  loading,
  selectedItems,
  selectAll,
  showAdvancedSearch,
  showExportMenu,
  search,
  clearFilters,
  sort,
  toggleSelectAll,
  bulkAction,
  exportData,
  deleteItem,
  formatDate
} = ' . $composableName . '(props.' . $modelVariablePlural . ', props.filters)

const debouncedSearch = debounce(search, 300)

function toggleAdvancedSearch() {
  showAdvancedSearch.value = !showAdvancedSearch.value
}

onMounted(() => {
  // Initialize component
})
</script>';
    }

    /**
     * Get Vue composable implementation.
     */
    protected function getVueComposable(): string
    {
        $modelVariable = $this->getModelVariable();
        $modelVariablePlural = Str::plural($modelVariable);
        $routeName = $this->getRouteName();

        return 'import { ref, reactive, computed } from \'vue\'
import { router } from \'@inertiajs/vue3\'
import axios from \'axios\'

export function use' . Str::studly($modelVariablePlural) . 'Datatable(initialData, initialFilters) {
  const ' . $modelVariablePlural . ' = ref(initialData)
  const loading = ref(false)
  const selectedItems = ref([])
  const selectAll = ref(false)
  const showAdvancedSearch = ref(false)
  const showExportMenu = ref(false)

  const filters = reactive({
    search: initialFilters.search || \'\',
    sort: initialFilters.sort || \'id\',
    direction: initialFilters.direction || \'asc\',
    per_page: initialFilters.per_page || 15,
    ' . ($this->options['withSearch'] ? '
    name: initialFilters.name || \'\',
    email: initialFilters.email || \'\',
    date_from: initialFilters.date_from || \'\',
    date_to: initialFilters.date_to || \'\',' : '') . '
  })

  function search() {
    loading.value = true
    
    router.get(route(\'' . $routeName . '.index\'), filters, {
      preserveState: true,
      preserveScroll: true,
      onSuccess: (page) => {
        ' . $modelVariablePlural . '.value = page.props.' . $modelVariablePlural . '
        loading.value = false
      },
      onError: () => {
        loading.value = false
      }
    })
  }

  function clearFilters() {
    filters.search = \'\'
    ' . ($this->options['withSearch'] ? '
    filters.name = \'\'
    filters.email = \'\'
    filters.date_from = \'\'
    filters.date_to = \'\'' : '') . '
    showAdvancedSearch.value = false
    search()
  }

  function sort(field) {
    if (filters.sort === field) {
      filters.direction = filters.direction === \'asc\' ? \'desc\' : \'asc\'
    } else {
      filters.sort = field
      filters.direction = \'asc\'
    }
    search()
  }

  ' . ($this->options['withBulk'] ? '
  function toggleSelectAll() {
    if (selectAll.value) {
      selectedItems.value = ' . $modelVariablePlural . '.value.data.map(item => item.id)
    } else {
      selectedItems.value = []
    }
  }

  async function bulkAction(action) {
    if (selectedItems.value.length === 0) {
      alert(\'No items selected\')
      return
    }

    if (action === \'delete\' && !confirm(\'Are you sure you want to delete selected items?\')) {
      return
    }

    try {
      loading.value = true
      await axios.post(route(\'' . $routeName . '.bulk-action\'), {
        action,
        ids: selectedItems.value
      })

      selectedItems.value = []
      selectAll.value = false
      search()
    } catch (error) {
      console.error(\'Bulk action failed:\', error)
      alert(\'Action failed. Please try again.\')
    } finally {
      loading.value = false
    }
  }' : '') . '

  ' . ($this->options['withExports'] ? '
  async function exportData(format) {
    try {
      showExportMenu.value = false
      loading.value = true

      const response = await axios.get(route(\'' . $routeName . '.export-\' + format), {
        params: filters,
        responseType: \'blob\'
      })

      // Create download link
      const blob = new Blob([response.data])
      const url = window.URL.createObjectURL(blob)
      const link = document.createElement(\'a\')
      link.href = url
      link.download = `' . $modelVariablePlural . '.${format}`
      document.body.appendChild(link)
      link.click()
      document.body.removeChild(link)
      window.URL.revokeObjectURL(url)
    } catch (error) {
      console.error(\'Export failed:\', error)
      alert(\'Export failed. Please try again.\')
    } finally {
      loading.value = false
    }
  }' : '') . '

  async function deleteItem(id) {
    if (!confirm(\'Are you sure you want to delete this item?\')) {
      return
    }

    try {
      loading.value = true
      await axios.delete(route(\'' . $routeName . '.destroy\', id))
      search()
    } catch (error) {
      console.error(\'Delete failed:\', error)
      alert(\'Delete failed. Please try again.\')
    } finally {
      loading.value = false
    }
  }

  function formatDate(date) {
    return new Date(date).toLocaleDateString(\'en-US\', {
      year: \'numeric\',
      month: \'short\',
      day: \'numeric\',
      hour: \'2-digit\',
      minute: \'2-digit\'
    })
  }

  return {
    ' . $modelVariablePlural . ',
    filters,
    loading,
    selectedItems,
    selectAll,
    showAdvancedSearch,
    showExportMenu,
    search,
    clearFilters,
    sort,
    ' . ($this->options['withBulk'] ? 'toggleSelectAll,
    bulkAction,' : '') . '
    ' . ($this->options['withExports'] ? 'exportData,' : '') . '
    deleteItem,
    formatDate
  }
}';
    }

    /**
     * Get clear cache method call.
     */
    protected function getClearCacheCall(): string
    {
        return ($this->options['cache'] ?? false) ? 'Cache::tags([\'inertia-datatable\'])->flush();' : '';
    }

    /**
     * Get table name for validation.
     */
    protected function getTableName(): string
    {
        return Str::snake(Str::plural($this->getModelClass()));
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
     * Get the controller namespace.
     */
    protected function getControllerNamespace(): string
    {
        $namespace = 'App\\Http\\Controllers';

        $modelPath = str_replace('/', '\\', $this->modelName);
        if (str_contains($modelPath, '\\')) {
            $parts = explode('\\', $modelPath);
            array_pop($parts);
            $namespace .= '\\' . implode('\\', $parts);
        }

        return $namespace;
    }

    /**
     * Get the controller name.
     */
    protected function getControllerName(): string
    {
        return $this->getModelClass() . 'Controller';
    }

    /**
     * Get the model variable name.
     */
    protected function getModelVariable(): string
    {
        return Str::camel($this->getModelClass());
    }

    /**
     * Get the route name prefix.
     */
    protected function getRouteName(): string
    {
        $parts = explode('/', $this->modelName);
        $names = array_map(fn($part) => Str::kebab($part), $parts);
        return implode('.', $names);
    }

    /**
     * Get Vue component name.
     */
    protected function getComponentName(): string
    {
        return $this->getModelClass() . '/Index';
    }

    /**
     * Get composable name.
     */
    protected function getComposableName(): string
    {
        return 'use' . Str::studly(Str::plural($this->getModelVariable())) . 'Datatable';
    }

    /**
     * Get the controller file path.
     */
    protected function getControllerPath(): string
    {
        $path = app_path('Http/Controllers');

        $modelPath = str_replace('\\', '/', $this->modelName);
        if (str_contains($modelPath, '/')) {
            $parts = explode('/', $modelPath);
            array_pop($parts);
            $path .= '/' . implode('/', $parts);
        }

        return $path . '/' . $this->getControllerName() . '.php';
    }

    /**
     * Get Vue component path.
     */
    protected function getVueComponentPath(): string
    {
        $parts = explode('/', $this->modelName);
        $paths = array_map(fn($part) => Str::studly($part), $parts);
        
        $componentPath = resource_path('js/Pages/' . implode('/', $paths));
        
        return $componentPath . '/Index.vue';
    }

    /**
     * Get composable path.
     */
    protected function getComposablePath(): string
    {
        return resource_path('js/Composables/' . $this->getComposableName() . '.js');
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