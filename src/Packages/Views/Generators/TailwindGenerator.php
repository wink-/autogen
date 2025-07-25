<?php

declare(strict_types=1);

namespace AutoGen\Packages\Views\Generators;

use Illuminate\Support\Str;

class TailwindGenerator extends BaseFrameworkGenerator
{
    public function generateView(string $viewType): string
    {
        return match ($viewType) {
            'index' => $this->generateIndexView(),
            'create' => $this->generateCreateView(),
            'edit' => $this->generateEditView(),
            'show' => $this->generateShowView(),
            'form' => $this->generateFormPartial(),
            'table' => $this->generateTablePartial(),
            'filters' => $this->generateFiltersPartial(),
            default => throw new \InvalidArgumentException("Unknown view type: {$viewType}")
        };
    }

    protected function generateIndexView(): string
    {
        $modelVariable = $this->getModelPluralVariable();
        $modelName = $this->modelBaseName;
        $routeName = $this->getRouteResourceName();
        
        $view = "@extends('{$this->layout}')

@section('title', '{$modelName} Management')

@section('content')
<div class=\"container mx-auto px-4 py-8\">
    <div class=\"bg-white shadow-lg rounded-lg overflow-hidden\">
        <!-- Header -->
        <div class=\"bg-gray-50 px-6 py-4 border-b border-gray-200\">
            <div class=\"flex justify-between items-center\">
                <div>
                    <h1 class=\"text-2xl font-bold text-gray-900\">{$modelName} Management</h1>
                    <p class=\"text-sm text-gray-600 mt-1\">Manage your {$modelVariable}</p>
                </div>
                <div class=\"flex space-x-2\">
";

        if ($this->withSearch) {
            $view .= "                    <button onclick=\"toggleFilters()\" class=\"inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500\">
                        <svg class=\"w-4 h-4 mr-2\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\">
                            <path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z\"></path>
                        </svg>
                        Filters
                    </button>
";
        }

        $view .= "                    <a href=\"{{ route('{$routeName}.create') }}\" class=\"inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:border-indigo-900 focus:ring ring-indigo-300 disabled:opacity-25 transition ease-in-out duration-150\">
                        <svg class=\"w-4 h-4 mr-2\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\">
                            <path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M12 6v6m0 0v6m0-6h6m-6 0H6\"></path>
                        </svg>
                        Add {$modelName}
                    </a>
                </div>
            </div>
        </div>

        <!-- Flash Messages -->
        @if(session('success'))
            <div class=\"bg-green-100 border border-green-400 text-green-700 px-4 py-3\" role=\"alert\">
                <span class=\"block sm:inline\">{{ session('success') }}</span>
            </div>
        @endif

        @if(session('error'))
            <div class=\"bg-red-100 border border-red-400 text-red-700 px-4 py-3\" role=\"alert\">
                <span class=\"block sm:inline\">{{ session('error') }}</span>
            </div>
        @endif
";

        if ($this->withSearch) {
            $view .= "
        <!-- Filters -->
        <div id=\"filters-section\" class=\"hidden bg-gray-50 px-6 py-4 border-b border-gray-200\">
            @include('{$this->getViewPath()}.filters')
        </div>
";
        }

        if ($this->withDatatable) {
            $view .= "
        <!-- DataTable -->
        <div class=\"p-6\">
            <div id=\"{$modelVariable}-table\"></div>
        </div>
";
        } else {
            $view .= "
        <!-- Table -->
        @include('{$this->getViewPath()}.table', ['{$modelVariable}' => \${$modelVariable}])
";
        }

        $view .= "    </div>
</div>
";

        if ($this->withModals) {
            $view .= "
<!-- Delete Confirmation Modal -->
<div id=\"deleteModal\" class=\"fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50\">
    <div class=\"relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white\">
        <div class=\"mt-3 text-center\">
            <div class=\"mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100\">
                <svg class=\"h-6 w-6 text-red-600\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\">
                    <path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.5 0L4.268 14.5c-.77.833.192 2.5 1.732 2.5z\"></path>
                </svg>
            </div>
            <h3 class=\"text-lg leading-6 font-medium text-gray-900 mt-4\">Delete {$modelName}</h3>
            <div class=\"mt-2 px-7 py-3\">
                <p class=\"text-sm text-gray-500\">Are you sure you want to delete this {$modelVariable}? This action cannot be undone.</p>
            </div>
            <div class=\"items-center px-4 py-3\">
                <button id=\"deleteConfirm\" class=\"px-4 py-2 bg-red-500 text-white text-base font-medium rounded-md w-24 mr-2 hover:bg-red-600 focus:outline-none focus:ring-2 focus:ring-red-300\">
                    Delete
                </button>
                <button onclick=\"closeDeleteModal()\" class=\"px-4 py-2 bg-gray-500 text-white text-base font-medium rounded-md w-24 hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-300\">
                    Cancel
                </button>
            </div>
        </div>
    </div>
</div>
";
        }

        $view .= "
@push('scripts')
<script>
";

        if ($this->withSearch) {
            $view .= "
function toggleFilters() {
    const filtersSection = document.getElementById('filters-section');
    filtersSection.classList.toggle('hidden');
}
";
        }

        if ($this->withModals) {
            $view .= "
let deleteForm = null;

function confirmDelete(form) {
    deleteForm = form;
    document.getElementById('deleteModal').classList.remove('hidden');
}

function closeDeleteModal() {
    document.getElementById('deleteModal').classList.add('hidden');
    deleteForm = null;
}

document.getElementById('deleteConfirm').addEventListener('click', function() {
    if (deleteForm) {
        deleteForm.submit();
    }
});
";
        }

        if ($this->withDatatable) {
            $view .= "
// Initialize DataTable
$(document).ready(function() {
    $('#{$modelVariable}-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: '{{ route(\"{$routeName}.index\") }}',
        columns: [
";
            
            foreach ($this->getTableColumns() as $column) {
                $view .= "            {data: '{$column}', name: '{$column}'},\n";
            }
            
            $view .= "            {data: 'action', name: 'action', orderable: false, searchable: false}
        ],
        responsive: true,
        order: [[0, 'desc']],
        pageLength: 25,
        language: {
            processing: '<div class=\"flex justify-center items-center\"><div class=\"animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600\"></div></div>'
        }
    });
});
";
        }

        $view .= "
</script>
@endpush
@endsection";

        return $view;
    }

    protected function generateCreateView(): string
    {
        $modelName = $this->modelBaseName;
        $modelVariable = $this->getModelVariable();
        $routeName = $this->getRouteResourceName();

        return "@extends('{$this->layout}')

@section('title', 'Create {$modelName}')

@section('content')
<div class=\"container mx-auto px-4 py-8\">
    <div class=\"max-w-2xl mx-auto\">
        <!-- Header -->
        <div class=\"mb-6\">
            <div class=\"flex items-center justify-between\">
                <div>
                    <h1 class=\"text-2xl font-bold text-gray-900\">Create {$modelName}</h1>
                    <p class=\"text-sm text-gray-600 mt-1\">Add a new {$modelVariable} to the system</p>
                </div>
                <a href=\"{{ route('{$routeName}.index') }}\" class=\"inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:outline-none focus:border-gray-700 focus:ring ring-gray-300 disabled:opacity-25 transition ease-in-out duration-150\">
                    <svg class=\"w-4 h-4 mr-2\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\">
                        <path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M10 19l-7-7m0 0l7-7m-7 7h18\"></path>
                    </svg>
                    Back to List
                </a>
            </div>
        </div>

        <!-- Form Card -->
        <div class=\"bg-white shadow-lg rounded-lg overflow-hidden\">
            <form method=\"POST\" action=\"{{ route('{$routeName}.store') }}\" class=\"p-6\">
                @csrf
                @include('{$this->getViewPath()}._form')
                
                <div class=\"flex items-center justify-end mt-6 pt-6 border-t border-gray-200\">
                    <a href=\"{{ route('{$routeName}.index') }}\" class=\"bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded-l\">
                        Cancel
                    </a>
                    <button type=\"submit\" class=\"bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded-r\">
                        Create {$modelName}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection";
    }

    protected function generateEditView(): string
    {
        $modelName = $this->modelBaseName;
        $modelVariable = $this->getModelVariable();
        $routeName = $this->getRouteResourceName();

        return "@extends('{$this->layout}')

@section('title', 'Edit {$modelName}')

@section('content')
<div class=\"container mx-auto px-4 py-8\">
    <div class=\"max-w-2xl mx-auto\">
        <!-- Header -->
        <div class=\"mb-6\">
            <div class=\"flex items-center justify-between\">
                <div>
                    <h1 class=\"text-2xl font-bold text-gray-900\">Edit {$modelName}</h1>
                    <p class=\"text-sm text-gray-600 mt-1\">Update {$modelVariable} information</p>
                </div>
                <div class=\"flex space-x-2\">
                    <a href=\"{{ route('{$routeName}.show', \${$modelVariable}) }}\" class=\"inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:outline-none focus:border-blue-700 focus:ring ring-blue-300 disabled:opacity-25 transition ease-in-out duration-150\">
                        <svg class=\"w-4 h-4 mr-2\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\">
                            <path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M15 12a3 3 0 11-6 0 3 3 0 016 0z\"></path>
                            <path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z\"></path>
                        </svg>
                        View
                    </a>
                    <a href=\"{{ route('{$routeName}.index') }}\" class=\"inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:outline-none focus:border-gray-700 focus:ring ring-gray-300 disabled:opacity-25 transition ease-in-out duration-150\">
                        <svg class=\"w-4 h-4 mr-2\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\">
                            <path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M10 19l-7-7m0 0l7-7m-7 7h18\"></path>
                        </svg>
                        Back to List
                    </a>
                </div>
            </div>
        </div>

        <!-- Form Card -->
        <div class=\"bg-white shadow-lg rounded-lg overflow-hidden\">
            <form method=\"POST\" action=\"{{ route('{$routeName}.update', \${$modelVariable}) }}\" class=\"p-6\">
                @csrf
                @method('PATCH')
                @include('{$this->getViewPath()}._form')
                
                <div class=\"flex items-center justify-end mt-6 pt-6 border-t border-gray-200\">
                    <a href=\"{{ route('{$routeName}.show', \${$modelVariable}) }}\" class=\"bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded-l\">
                        Cancel
                    </a>
                    <button type=\"submit\" class=\"bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded-r\">
                        Update {$modelName}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection";
    }

    protected function generateShowView(): string
    {
        $modelName = $this->modelBaseName;
        $modelVariable = $this->getModelVariable();
        $routeName = $this->getRouteResourceName();

        $view = "@extends('{$this->layout}')

@section('title', '{$modelName} Details')

@section('content')
<div class=\"container mx-auto px-4 py-8\">
    <div class=\"max-w-4xl mx-auto\">
        <!-- Header -->
        <div class=\"mb-6\">
            <div class=\"flex items-center justify-between\">
                <div>
                    <h1 class=\"text-2xl font-bold text-gray-900\">{$modelName} Details</h1>
                    <p class=\"text-sm text-gray-600 mt-1\">View {$modelVariable} information</p>
                </div>
                <div class=\"flex space-x-2\">
                    <a href=\"{{ route('{$routeName}.edit', \${$modelVariable}) }}\" class=\"inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:outline-none focus:border-indigo-700 focus:ring ring-indigo-300 disabled:opacity-25 transition ease-in-out duration-150\">
                        <svg class=\"w-4 h-4 mr-2\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\">
                            <path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z\"></path>
                        </svg>
                        Edit
                    </a>
";

        if ($this->withModals) {
            $view .= "                    <form method=\"POST\" action=\"{{ route('{$routeName}.destroy', \${$modelVariable}) }}\" style=\"display: inline;\" onsubmit=\"return confirmDelete(this)\">
                        @csrf
                        @method('DELETE')
                        <button type=\"submit\" class=\"inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 focus:outline-none focus:border-red-700 focus:ring ring-red-300 disabled:opacity-25 transition ease-in-out duration-150\">
                            <svg class=\"w-4 h-4 mr-2\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\">
                                <path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16\"></path>
                            </svg>
                            Delete
                        </button>
                    </form>
";
        } else {
            $view .= "                    <form method=\"POST\" action=\"{{ route('{$routeName}.destroy', \${$modelVariable}) }}\" style=\"display: inline;\" onsubmit=\"return confirm('Are you sure you want to delete this {$modelVariable}?')\">
                        @csrf
                        @method('DELETE')
                        <button type=\"submit\" class=\"inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 focus:outline-none focus:border-red-700 focus:ring ring-red-300 disabled:opacity-25 transition ease-in-out duration-150\">
                            <svg class=\"w-4 h-4 mr-2\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\">
                                <path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16\"></path>
                            </svg>
                            Delete
                        </button>
                    </form>
";
        }

        $view .= "                    <a href=\"{{ route('{$routeName}.index') }}\" class=\"inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:outline-none focus:border-gray-700 focus:ring ring-gray-300 disabled:opacity-25 transition ease-in-out duration-150\">
                        <svg class=\"w-4 h-4 mr-2\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\">
                            <path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M10 19l-7-7m0 0l7-7m-7 7h18\"></path>
                        </svg>
                        Back to List
                    </a>
                </div>
            </div>
        </div>

        <!-- Content Card -->
        <div class=\"bg-white shadow-lg rounded-lg overflow-hidden\">
            <div class=\"px-6 py-4 bg-gray-50 border-b border-gray-200\">
                <h3 class=\"text-lg leading-6 font-medium text-gray-900\">{$modelName} Information</h3>
            </div>
            
            <div class=\"px-6 py-4\">
                <dl class=\"grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2\">
";

        // Add ID field
        $view .= "                    <div>
                        <dt class=\"text-sm font-medium text-gray-500\">ID</dt>
                        <dd class=\"mt-1 text-sm text-gray-900\">{{ \${$modelVariable}->{$this->primaryKey} }}</dd>
                    </div>
";

        // Add fillable fields
        foreach ($this->fillableFields as $field) {
            $label = $this->getFieldLabel($field);
            $fieldType = $this->getFieldType($field);
            
            $view .= "                    <div>
                        <dt class=\"text-sm font-medium text-gray-500\">{$label}</dt>
                        <dd class=\"mt-1 text-sm text-gray-900\">";
            
            if ($fieldType === 'checkbox') {
                $view .= "
                            <span class=\"inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ \${$modelVariable}->{$field} ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}\">
                                {{ \${$modelVariable}->{$field} ? 'Yes' : 'No' }}
                            </span>";
            } elseif ($fieldType === 'textarea') {
                $view .= "
                            <div class=\"prose max-w-none text-sm\">
                                {!! nl2br(e(\${$modelVariable}->{$field})) !!}
                            </div>";
            } elseif (in_array($fieldType, ['date', 'datetime-local'])) {
                $view .= "
                            {{ \${$modelVariable}->{$field} ? \${$modelVariable}->{$field}->format('M j, Y' . (\${$modelVariable}->{$field}->format('H:i') !== '00:00' ? ' g:i A' : '')) : 'Not set' }}";
            } else {
                $view .= "{{ \${$modelVariable}->{$field} ?? 'Not set' }}";
            }
            
            $view .= "
                        </dd>
                    </div>
";
        }

        // Add timestamps if they exist
        if (in_array('created_at', $this->columns)) {
            $view .= "                    <div>
                        <dt class=\"text-sm font-medium text-gray-500\">Created At</dt>
                        <dd class=\"mt-1 text-sm text-gray-900\">{{ \${$modelVariable}->created_at->format('M j, Y g:i A') }}</dd>
                    </div>
";
        }

        if (in_array('updated_at', $this->columns)) {
            $view .= "                    <div>
                        <dt class=\"text-sm font-medium text-gray-500\">Updated At</dt>
                        <dd class=\"mt-1 text-sm text-gray-900\">{{ \${$modelVariable}->updated_at->format('M j, Y g:i A') }}</dd>
                    </div>
";
        }

        $view .= "                </dl>
            </div>
        </div>
    </div>
</div>
";

        if ($this->withModals) {
            $view .= "
<!-- Delete Confirmation Modal -->
<div id=\"deleteModal\" class=\"fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50\">
    <div class=\"relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white\">
        <div class=\"mt-3 text-center\">
            <div class=\"mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100\">
                <svg class=\"h-6 w-6 text-red-600\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\">
                    <path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.5 0L4.268 14.5c-.77.833.192 2.5 1.732 2.5z\"></path>
                </svg>
            </div>
            <h3 class=\"text-lg leading-6 font-medium text-gray-900 mt-4\">Delete {$modelName}</h3>
            <div class=\"mt-2 px-7 py-3\">
                <p class=\"text-sm text-gray-500\">Are you sure you want to delete this {$modelVariable}? This action cannot be undone.</p>
            </div>
            <div class=\"items-center px-4 py-3\">
                <button onclick=\"confirmDelete()\" class=\"px-4 py-2 bg-red-500 text-white text-base font-medium rounded-md w-24 mr-2 hover:bg-red-600 focus:outline-none focus:ring-2 focus:ring-red-300\">
                    Delete
                </button>
                <button onclick=\"closeDeleteModal()\" class=\"px-4 py-2 bg-gray-500 text-white text-base font-medium rounded-md w-24 hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-300\">
                    Cancel
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
let deleteForm = null;

function confirmDelete(form) {
    deleteForm = form;
    document.getElementById('deleteModal').classList.remove('hidden');
    return false; // Prevent default form submission
}

function closeDeleteModal() {
    document.getElementById('deleteModal').classList.add('hidden');
    deleteForm = null;
}

// Handle the actual deletion
document.addEventListener('DOMContentLoaded', function() {
    const deleteButton = document.querySelector('#deleteModal button[onclick=\"confirmDelete()\"]');
    if (deleteButton) {
        deleteButton.onclick = function() {
            if (deleteForm) {
                deleteForm.submit();
            }
        };
    }
});
</script>
@endpush
";
        }

        $view .= "@endsection";

        return $view;
    }

    protected function generateFormPartial(): string
    {
        $modelVariable = $this->getModelVariable();
        
        $view = "<!-- Form Fields -->\n";
        
        foreach ($this->fillableFields as $field) {
            $label = $this->getFieldLabel($field);
            $type = $this->getFieldType($field);
            $placeholder = $this->getFieldPlaceholder($field);
            $validationAttrs = implode(' ', $this->getValidationAttributes($field));
            
            $view .= "<div class=\"mb-6\">\n";
            $view .= "    <label for=\"{$field}\" class=\"block text-sm font-medium text-gray-700 mb-2\">{$label}</label>\n";
            
            if ($type === 'textarea') {
                $view .= "    <textarea name=\"{$field}\" id=\"{$field}\" rows=\"4\" class=\"w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 @error('{$field}') border-red-500 @enderror\" placeholder=\"{$placeholder}\" {$validationAttrs}>{{ old('{$field}', \${$modelVariable}->{$field} ?? '') }}</textarea>\n";
            } elseif ($type === 'checkbox') {
                $view .= "    <div class=\"flex items-center\">\n";
                $view .= "        <input type=\"checkbox\" name=\"{$field}\" id=\"{$field}\" value=\"1\" class=\"h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded @error('{$field}') border-red-500 @enderror\" {{ old('{$field}', \${$modelVariable}->{$field} ?? false) ? 'checked' : '' }} {$validationAttrs}>\n";
                $view .= "        <label for=\"{$field}\" class=\"ml-2 block text-sm text-gray-900\">{$label}</label>\n";
                $view .= "    </div>\n";
            } else {
                $view .= "    <input type=\"{$type}\" name=\"{$field}\" id=\"{$field}\" class=\"w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 @error('{$field}') border-red-500 @enderror\" placeholder=\"{$placeholder}\" value=\"{{ old('{$field}', \${$modelVariable}->{$field} ?? '') }}\" {$validationAttrs}>\n";
            }
            
            $view .= "    @error('{$field}')\n";
            $view .= "        <p class=\"mt-1 text-sm text-red-600\">{{ \$message }}</p>\n";
            $view .= "    @enderror\n";
            $view .= "</div>\n\n";
        }
        
        return $view;
    }

    protected function generateTablePartial(): string
    {
        $modelVariable = $this->getModelPluralVariable();
        $singleModelVariable = $this->getModelVariable();
        $routeName = $this->getRouteResourceName();
        $columns = $this->getTableColumns();

        $view = "<div class=\"overflow-hidden\">
    <div class=\"overflow-x-auto\">
        <table class=\"min-w-full divide-y divide-gray-200\">
            <thead class=\"bg-gray-50\">
                <tr>
";

        foreach ($columns as $column) {
            $label = $this->getFieldLabel($column);
            $view .= "                    <th scope=\"col\" class=\"px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider\">{$label}</th>\n";
        }

        $view .= "                    <th scope=\"col\" class=\"px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider\">Actions</th>
                </tr>
            </thead>
            <tbody class=\"bg-white divide-y divide-gray-200\">
                @forelse(\${$modelVariable} as \${$singleModelVariable})
                    <tr class=\"hover:bg-gray-50\">
";

        foreach ($columns as $column) {
            $fieldType = $this->getFieldType($column);
            
            $view .= "                        <td class=\"px-6 py-4 whitespace-nowrap text-sm text-gray-900\">";
            
            if ($fieldType === 'checkbox') {
                $view .= "
                            <span class=\"inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ \${$singleModelVariable}->{$column} ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}\">
                                {{ \${$singleModelVariable}->{$column} ? 'Yes' : 'No' }}
                            </span>";
            } elseif (in_array($fieldType, ['date', 'datetime-local']) && in_array($column, ['created_at', 'updated_at'])) {
                $view .= "{{ \${$singleModelVariable}->{$column}->format('M j, Y') }}";
            } elseif ($fieldType === 'textarea') {
                $view .= "{{ Str::limit(\${$singleModelVariable}->{$column}, 50) }}";
            } else {
                $view .= "{{ \${$singleModelVariable}->{$column} }}";
            }
            
            $view .= "</td>\n";
        }

        $view .= "                        <td class=\"px-6 py-4 whitespace-nowrap text-right text-sm font-medium\">
                            <div class=\"flex justify-end space-x-2\">
                                <a href=\"{{ route('{$routeName}.show', \${$singleModelVariable}) }}\" class=\"text-blue-600 hover:text-blue-900\" title=\"View\">
                                    <svg class=\"w-4 h-4\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\">
                                        <path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M15 12a3 3 0 11-6 0 3 3 0 016 0z\"></path>
                                        <path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z\"></path>
                                    </svg>
                                </a>
                                <a href=\"{{ route('{$routeName}.edit', \${$singleModelVariable}) }}\" class=\"text-indigo-600 hover:text-indigo-900\" title=\"Edit\">
                                    <svg class=\"w-4 h-4\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\">
                                        <path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z\"></path>
                                    </svg>
                                </a>
";

        if ($this->withModals) {
            $view .= "                                <form method=\"POST\" action=\"{{ route('{$routeName}.destroy', \${$singleModelVariable}) }}\" style=\"display: inline;\" onsubmit=\"return confirmDelete(this)\">
                                    @csrf
                                    @method('DELETE')
                                    <button type=\"submit\" class=\"text-red-600 hover:text-red-900\" title=\"Delete\">
                                        <svg class=\"w-4 h-4\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\">
                                            <path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16\"></path>
                                        </svg>
                                    </button>
                                </form>
";
        } else {
            $view .= "                                <form method=\"POST\" action=\"{{ route('{$routeName}.destroy', \${$singleModelVariable}) }}\" style=\"display: inline;\" onsubmit=\"return confirm('Are you sure you want to delete this {$singleModelVariable}?')\">
                                    @csrf
                                    @method('DELETE')
                                    <button type=\"submit\" class=\"text-red-600 hover:text-red-900\" title=\"Delete\">
                                        <svg class=\"w-4 h-4\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\">
                                            <path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16\"></path>
                                        </svg>
                                    </button>
                                </form>
";
        }

        $view .= "                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan=\"" . (count($columns) + 1) . "\" class=\"px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center\">
                            <div class=\"flex flex-col items-center justify-center py-8\">
                                <svg class=\"w-12 h-12 text-gray-400 mb-4\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\">
                                    <path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4\"></path>
                                </svg>
                                <p class=\"text-lg font-medium text-gray-900 mb-1\">No {$modelVariable} found</p>
                                <p class=\"text-sm text-gray-500 mb-4\">Get started by creating a new {$singleModelVariable}.</p>
                                <a href=\"{{ route('{$routeName}.create') }}\" class=\"inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:outline-none focus:border-indigo-700 focus:ring ring-indigo-300 disabled:opacity-25 transition ease-in-out duration-150\">
                                    <svg class=\"w-4 h-4 mr-2\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\">
                                        <path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M12 6v6m0 0v6m0-6h6m-6 0H6\"></path>
                                    </svg>
                                    Add {$this->modelBaseName}
                                </a>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
    <!-- Pagination -->
    @if(method_exists(\${$modelVariable}, 'links'))
        <div class=\"px-6 py-3 bg-gray-50 border-t border-gray-200\">
            {{ \${$modelVariable}->links() }}
        </div>
    @endif
</div>";

        return $view;
    }

    protected function generateFiltersPartial(): string
    {
        $searchableFields = array_filter($this->fillableFields, function($field) {
            $type = $this->getFieldType($field);
            return in_array($type, ['text', 'email', 'textarea']);
        });

        $view = "<form method=\"GET\" action=\"{{ route('{$this->getRouteResourceName()}.index') }}\" class=\"grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3\">
    <!-- Search -->
    <div>
        <label for=\"search\" class=\"block text-sm font-medium text-gray-700 mb-2\">Search</label>
        <input type=\"text\" name=\"search\" id=\"search\" class=\"w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500\" placeholder=\"Search...\" value=\"{{ request('search') }}\">
    </div>
";

        // Add status filter if exists
        if (in_array('status', $this->fillableFields)) {
            $view .= "
    <!-- Status -->
    <div>
        <label for=\"status\" class=\"block text-sm font-medium text-gray-700 mb-2\">Status</label>
        <select name=\"status\" id=\"status\" class=\"w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500\">
            <option value=\"\">All Statuses</option>
            <option value=\"active\" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
            <option value=\"inactive\" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
        </select>
    </div>
";
        }

        // Add date range filter
        if (in_array('created_at', $this->columns)) {
            $view .= "
    <!-- Date Range -->
    <div>
        <label for=\"date_from\" class=\"block text-sm font-medium text-gray-700 mb-2\">Date From</label>
        <input type=\"date\" name=\"date_from\" id=\"date_from\" class=\"w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500\" value=\"{{ request('date_from') }}\">
    </div>
    
    <div>
        <label for=\"date_to\" class=\"block text-sm font-medium text-gray-700 mb-2\">Date To</label>
        <input type=\"date\" name=\"date_to\" id=\"date_to\" class=\"w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500\" value=\"{{ request('date_to') }}\">
    </div>
";
        }

        $view .= "
    <!-- Actions -->
    <div class=\"flex items-end space-x-2\">
        <button type=\"submit\" class=\"inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:outline-none focus:border-indigo-700 focus:ring ring-indigo-300 disabled:opacity-25 transition ease-in-out duration-150\">
            <svg class=\"w-4 h-4 mr-2\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\">
                <path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z\"></path>
            </svg>
            Filter
        </button>
        <a href=\"{{ route('{$this->getRouteResourceName()}.index') }}\" class=\"inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:outline-none focus:border-gray-700 focus:ring ring-gray-300 disabled:opacity-25 transition ease-in-out duration-150\">
            Clear
        </a>
    </div>
</form>";

        return $view;
    }

    protected function getViewPath(): string
    {
        $parts = explode('/', $this->modelName);
        array_pop($parts); // Remove model name
        $parts[] = Str::snake($this->modelBaseName);
        
        return implode('.', array_map(fn($part) => Str::snake($part), $parts));
    }
}