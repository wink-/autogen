<?php

declare(strict_types=1);

namespace AutoGen\Packages\Views\Generators;

use Illuminate\Support\Str;

class BootstrapGenerator extends BaseFrameworkGenerator
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
<div class=\"container-fluid\">
    <div class=\"card shadow\">
        <!-- Header -->
        <div class=\"card-header bg-light\">
            <div class=\"d-flex justify-content-between align-items-center\">
                <div>
                    <h1 class=\"h3 mb-1 text-gray-800\">{$modelName} Management</h1>
                    <p class=\"text-muted mb-0\">Manage your {$modelVariable}</p>
                </div>
                <div class=\"d-flex gap-2\">
";

        if ($this->withSearch) {
            $view .= "                    <button type=\"button\" class=\"btn btn-outline-secondary\" onclick=\"toggleFilters()\">
                        <i class=\"fas fa-filter\"></i> Filters
                    </button>
";
        }

        $view .= "                    <a href=\"{{ route('{$routeName}.create') }}\" class=\"btn btn-primary\">
                        <i class=\"fas fa-plus\"></i> Add {$modelName}
                    </a>
                </div>
            </div>
        </div>

        <!-- Flash Messages -->
        @if(session('success'))
            <div class=\"alert alert-success alert-dismissible fade show m-3\" role=\"alert\">
                {{ session('success') }}
                <button type=\"button\" class=\"btn-close\" data-bs-dismiss=\"alert\"></button>
            </div>
        @endif

        @if(session('error'))
            <div class=\"alert alert-danger alert-dismissible fade show m-3\" role=\"alert\">
                {{ session('error') }}
                <button type=\"button\" class=\"btn-close\" data-bs-dismiss=\"alert\"></button>
            </div>
        @endif
";

        if ($this->withSearch) {
            $view .= "
        <!-- Filters -->
        <div id=\"filters-section\" class=\"card-body border-bottom d-none\">
            @include('{$this->getViewPath()}.filters')
        </div>
";
        }

        if ($this->withDatatable) {
            $view .= "
        <!-- DataTable -->
        <div class=\"card-body\">
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
<div class=\"modal fade\" id=\"deleteModal\" tabindex=\"-1\">
    <div class=\"modal-dialog\">
        <div class=\"modal-content\">
            <div class=\"modal-header\">
                <h5 class=\"modal-title\">Delete {$modelName}</h5>
                <button type=\"button\" class=\"btn-close\" data-bs-dismiss=\"modal\"></button>
            </div>
            <div class=\"modal-body\">
                <div class=\"text-center\">
                    <i class=\"fas fa-exclamation-triangle text-warning fa-3x mb-3\"></i>
                    <p>Are you sure you want to delete this {$modelVariable}? This action cannot be undone.</p>
                </div>
            </div>
            <div class=\"modal-footer\">
                <button type=\"button\" class=\"btn btn-secondary\" data-bs-dismiss=\"modal\">Cancel</button>
                <button type=\"button\" class=\"btn btn-danger\" id=\"deleteConfirm\">Delete</button>
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
    filtersSection.classList.toggle('d-none');
}
";
        }

        if ($this->withModals) {
            $view .= "
let deleteForm = null;

function confirmDelete(form) {
    deleteForm = form;
    const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
    modal.show();
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
            processing: '<div class=\"d-flex justify-content-center\"><div class=\"spinner-border\" role=\"status\"><span class=\"visually-hidden\">Loading...</span></div></div>'
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
<div class=\"container-fluid\">
    <div class=\"row justify-content-center\">
        <div class=\"col-lg-8\">
            <!-- Header -->
            <div class=\"d-flex justify-content-between align-items-center mb-4\">
                <div>
                    <h1 class=\"h3 mb-1 text-gray-800\">Create {$modelName}</h1>
                    <p class=\"text-muted mb-0\">Add a new {$modelVariable} to the system</p>
                </div>
                <a href=\"{{ route('{$routeName}.index') }}\" class=\"btn btn-secondary\">
                    <i class=\"fas fa-arrow-left\"></i> Back to List
                </a>
            </div>

            <!-- Form Card -->
            <div class=\"card shadow\">
                <div class=\"card-body\">
                    <form method=\"POST\" action=\"{{ route('{$routeName}.store') }}\">
                        @csrf
                        @include('{$this->getViewPath()}._form')
                        
                        <div class=\"d-flex justify-content-end mt-4 pt-3 border-top\">
                            <a href=\"{{ route('{$routeName}.index') }}\" class=\"btn btn-secondary me-2\">Cancel</a>
                            <button type=\"submit\" class=\"btn btn-primary\">Create {$modelName}</button>
                        </div>
                    </form>
                </div>
            </div>
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
<div class=\"container-fluid\">
    <div class=\"row justify-content-center\">
        <div class=\"col-lg-8\">
            <!-- Header -->
            <div class=\"d-flex justify-content-between align-items-center mb-4\">
                <div>
                    <h1 class=\"h3 mb-1 text-gray-800\">Edit {$modelName}</h1>
                    <p class=\"text-muted mb-0\">Update {$modelVariable} information</p>
                </div>
                <div class=\"d-flex gap-2\">
                    <a href=\"{{ route('{$routeName}.show', \${$modelVariable}) }}\" class=\"btn btn-info\">
                        <i class=\"fas fa-eye\"></i> View
                    </a>
                    <a href=\"{{ route('{$routeName}.index') }}\" class=\"btn btn-secondary\">
                        <i class=\"fas fa-arrow-left\"></i> Back to List
                    </a>
                </div>
            </div>

            <!-- Form Card -->
            <div class=\"card shadow\">
                <div class=\"card-body\">
                    <form method=\"POST\" action=\"{{ route('{$routeName}.update', \${$modelVariable}) }}\">
                        @csrf
                        @method('PATCH')
                        @include('{$this->getViewPath()}._form')
                        
                        <div class=\"d-flex justify-content-end mt-4 pt-3 border-top\">
                            <a href=\"{{ route('{$routeName}.show', \${$modelVariable}) }}\" class=\"btn btn-secondary me-2\">Cancel</a>
                            <button type=\"submit\" class=\"btn btn-primary\">Update {$modelName}</button>
                        </div>
                    </form>
                </div>
            </div>
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
<div class=\"container-fluid\">
    <div class=\"row justify-content-center\">
        <div class=\"col-lg-10\">
            <!-- Header -->
            <div class=\"d-flex justify-content-between align-items-center mb-4\">
                <div>
                    <h1 class=\"h3 mb-1 text-gray-800\">{$modelName} Details</h1>
                    <p class=\"text-muted mb-0\">View {$modelVariable} information</p>
                </div>
                <div class=\"d-flex gap-2\">
                    <a href=\"{{ route('{$routeName}.edit', \${$modelVariable}) }}\" class=\"btn btn-primary\">
                        <i class=\"fas fa-edit\"></i> Edit
                    </a>
";

        if ($this->withModals) {
            $view .= "                    <form method=\"POST\" action=\"{{ route('{$routeName}.destroy', \${$modelVariable}) }}\" style=\"display: inline;\" onsubmit=\"return confirmDelete(this)\">
                        @csrf
                        @method('DELETE')
                        <button type=\"submit\" class=\"btn btn-danger\">
                            <i class=\"fas fa-trash\"></i> Delete
                        </button>
                    </form>
";
        } else {
            $view .= "                    <form method=\"POST\" action=\"{{ route('{$routeName}.destroy', \${$modelVariable}) }}\" style=\"display: inline;\" onsubmit=\"return confirm('Are you sure you want to delete this {$modelVariable}?')\">
                        @csrf
                        @method('DELETE')
                        <button type=\"submit\" class=\"btn btn-danger\">
                            <i class=\"fas fa-trash\"></i> Delete
                        </button>
                    </form>
";
        }

        $view .= "                    <a href=\"{{ route('{$routeName}.index') }}\" class=\"btn btn-secondary\">
                        <i class=\"fas fa-arrow-left\"></i> Back to List
                    </a>
                </div>
            </div>

            <!-- Content Card -->
            <div class=\"card shadow\">
                <div class=\"card-header bg-light\">
                    <h5 class=\"card-title mb-0\">{$modelName} Information</h5>
                </div>
                
                <div class=\"card-body\">
                    <div class=\"row\">
";

        // Add ID field
        $view .= "                        <div class=\"col-md-6 mb-3\">
                            <strong class=\"text-muted\">ID:</strong>
                            <div class=\"mt-1\">{{ \${$modelVariable}->{$this->primaryKey} }}</div>
                        </div>
";

        // Add fillable fields
        $fieldCount = 0;
        foreach ($this->fillableFields as $field) {
            $label = $this->getFieldLabel($field);
            $fieldType = $this->getFieldType($field);
            
            $view .= "                        <div class=\"col-md-6 mb-3\">
                            <strong class=\"text-muted\">{$label}:</strong>
                            <div class=\"mt-1\">";
            
            if ($fieldType === 'checkbox') {
                $view .= "
                                <span class=\"badge {{ \${$modelVariable}->{$field} ? 'bg-success' : 'bg-danger' }}\">
                                    {{ \${$modelVariable}->{$field} ? 'Yes' : 'No' }}
                                </span>";
            } elseif ($fieldType === 'textarea') {
                $view .= "
                                <div class=\"border rounded p-2 bg-light\">
                                    {!! nl2br(e(\${$modelVariable}->{$field})) !!}
                                </div>";
            } elseif (in_array($fieldType, ['date', 'datetime-local'])) {
                $view .= "
                                {{ \${$modelVariable}->{$field} ? \${$modelVariable}->{$field}->format('M j, Y' . (\${$modelVariable}->{$field}->format('H:i') !== '00:00' ? ' g:i A' : '')) : 'Not set' }}";
            } else {
                $view .= "{{ \${$modelVariable}->{$field} ?? 'Not set' }}";
            }
            
            $view .= "
                            </div>
                        </div>
";

            $fieldCount++;
            if ($fieldCount % 2 === 0) {
                $view .= "                    </div>
                    <div class=\"row\">
";
            }
        }

        // Add timestamps if they exist
        if (in_array('created_at', $this->columns)) {
            $view .= "                        <div class=\"col-md-6 mb-3\">
                            <strong class=\"text-muted\">Created At:</strong>
                            <div class=\"mt-1\">{{ \${$modelVariable}->created_at->format('M j, Y g:i A') }}</div>
                        </div>
";
        }

        if (in_array('updated_at', $this->columns)) {
            $view .= "                        <div class=\"col-md-6 mb-3\">
                            <strong class=\"text-muted\">Updated At:</strong>
                            <div class=\"mt-1\">{{ \${$modelVariable}->updated_at->format('M j, Y g:i A') }}</div>
                        </div>
";
        }

        $view .= "                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
";

        if ($this->withModals) {
            $view .= "
<!-- Delete Confirmation Modal -->
<div class=\"modal fade\" id=\"deleteModal\" tabindex=\"-1\">
    <div class=\"modal-dialog\">
        <div class=\"modal-content\">
            <div class=\"modal-header\">
                <h5 class=\"modal-title\">Delete {$modelName}</h5>
                <button type=\"button\" class=\"btn-close\" data-bs-dismiss=\"modal\"></button>
            </div>
            <div class=\"modal-body\">
                <div class=\"text-center\">
                    <i class=\"fas fa-exclamation-triangle text-warning fa-3x mb-3\"></i>
                    <p>Are you sure you want to delete this {$modelVariable}? This action cannot be undone.</p>
                </div>
            </div>
            <div class=\"modal-footer\">
                <button type=\"button\" class=\"btn btn-secondary\" data-bs-dismiss=\"modal\">Cancel</button>
                <button type=\"button\" class=\"btn btn-danger\" id=\"deleteConfirm\">Delete</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
let deleteForm = null;

function confirmDelete(form) {
    deleteForm = form;
    const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
    modal.show();
    return false; // Prevent default form submission
}

document.getElementById('deleteConfirm').addEventListener('click', function() {
    if (deleteForm) {
        deleteForm.submit();
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
            
            $view .= "<div class=\"mb-3\">\n";
            $view .= "    <label for=\"{$field}\" class=\"form-label\">{$label}</label>\n";
            
            if ($type === 'textarea') {
                $view .= "    <textarea name=\"{$field}\" id=\"{$field}\" rows=\"4\" class=\"form-control @error('{$field}') is-invalid @enderror\" placeholder=\"{$placeholder}\" {$validationAttrs}>{{ old('{$field}', \${$modelVariable}->{$field} ?? '') }}</textarea>\n";
            } elseif ($type === 'checkbox') {
                $view .= "    <div class=\"form-check\">\n";
                $view .= "        <input type=\"checkbox\" name=\"{$field}\" id=\"{$field}\" value=\"1\" class=\"form-check-input @error('{$field}') is-invalid @enderror\" {{ old('{$field}', \${$modelVariable}->{$field} ?? false) ? 'checked' : '' }} {$validationAttrs}>\n";
                $view .= "        <label for=\"{$field}\" class=\"form-check-label\">{$label}</label>\n";
                $view .= "    </div>\n";
            } else {
                $view .= "    <input type=\"{$type}\" name=\"{$field}\" id=\"{$field}\" class=\"form-control @error('{$field}') is-invalid @enderror\" placeholder=\"{$placeholder}\" value=\"{{ old('{$field}', \${$modelVariable}->{$field} ?? '') }}\" {$validationAttrs}>\n";
            }
            
            $view .= "    @error('{$field}')\n";
            $view .= "        <div class=\"invalid-feedback\">{{ \$message }}</div>\n";
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

        $view = "<div class=\"card-body p-0\">
    <div class=\"table-responsive\">
        <table class=\"table table-hover mb-0\">
            <thead class=\"table-light\">
                <tr>
";

        foreach ($columns as $column) {
            $label = $this->getFieldLabel($column);
            $view .= "                    <th>{$label}</th>\n";
        }

        $view .= "                    <th width=\"150\">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse(\${$modelVariable} as \${$singleModelVariable})
                    <tr>
";

        foreach ($columns as $column) {
            $fieldType = $this->getFieldType($column);
            
            $view .= "                        <td>";
            
            if ($fieldType === 'checkbox') {
                $view .= "
                            <span class=\"badge {{ \${$singleModelVariable}->{$column} ? 'bg-success' : 'bg-danger' }}\">
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

        $view .= "                        <td>
                            <div class=\"btn-group btn-group-sm\">
                                <a href=\"{{ route('{$routeName}.show', \${$singleModelVariable}) }}\" class=\"btn btn-outline-info\" title=\"View\">
                                    <i class=\"fas fa-eye\"></i>
                                </a>
                                <a href=\"{{ route('{$routeName}.edit', \${$singleModelVariable}) }}\" class=\"btn btn-outline-primary\" title=\"Edit\">
                                    <i class=\"fas fa-edit\"></i>
                                </a>
";

        if ($this->withModals) {
            $view .= "                                <form method=\"POST\" action=\"{{ route('{$routeName}.destroy', \${$singleModelVariable}) }}\" style=\"display: inline;\" onsubmit=\"return confirmDelete(this)\">
                                    @csrf
                                    @method('DELETE')
                                    <button type=\"submit\" class=\"btn btn-outline-danger\" title=\"Delete\">
                                        <i class=\"fas fa-trash\"></i>
                                    </button>
                                </form>
";
        } else {
            $view .= "                                <form method=\"POST\" action=\"{{ route('{$routeName}.destroy', \${$singleModelVariable}) }}\" style=\"display: inline;\" onsubmit=\"return confirm('Are you sure you want to delete this {$singleModelVariable}?')\">
                                    @csrf
                                    @method('DELETE')
                                    <button type=\"submit\" class=\"btn btn-outline-danger\" title=\"Delete\">
                                        <i class=\"fas fa-trash\"></i>
                                    </button>
                                </form>
";
        }

        $view .= "                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan=\"" . (count($columns) + 1) . "\" class=\"text-center py-5\">
                            <div class=\"text-muted\">
                                <i class=\"fas fa-inbox fa-3x mb-3\"></i>
                                <h5>No {$modelVariable} found</h5>
                                <p class=\"mb-3\">Get started by creating a new {$singleModelVariable}.</p>
                                <a href=\"{{ route('{$routeName}.create') }}\" class=\"btn btn-primary\">
                                    <i class=\"fas fa-plus\"></i> Add {$this->modelBaseName}
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
        <div class=\"card-footer\">
            {{ \${$modelVariable}->links() }}
        </div>
    @endif
</div>";

        return $view;
    }

    protected function generateFiltersPartial(): string
    {
        $view = "<form method=\"GET\" action=\"{{ route('{$this->getRouteResourceName()}.index') }}\" class=\"row g-3\">
    <!-- Search -->
    <div class=\"col-md-4\">
        <label for=\"search\" class=\"form-label\">Search</label>
        <input type=\"text\" name=\"search\" id=\"search\" class=\"form-control\" placeholder=\"Search...\" value=\"{{ request('search') }}\">
    </div>
";

        // Add status filter if exists
        if (in_array('status', $this->fillableFields)) {
            $view .= "
    <!-- Status -->
    <div class=\"col-md-3\">
        <label for=\"status\" class=\"form-label\">Status</label>
        <select name=\"status\" id=\"status\" class=\"form-select\">
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
    <div class=\"col-md-2\">
        <label for=\"date_from\" class=\"form-label\">Date From</label>
        <input type=\"date\" name=\"date_from\" id=\"date_from\" class=\"form-control\" value=\"{{ request('date_from') }}\">
    </div>
    
    <div class=\"col-md-2\">
        <label for=\"date_to\" class=\"form-label\">Date To</label>
        <input type=\"date\" name=\"date_to\" id=\"date_to\" class=\"form-control\" value=\"{{ request('date_to') }}\">
    </div>
";
        }

        $view .= "
    <!-- Actions -->
    <div class=\"col-md-1 d-flex align-items-end\">
        <div class=\"d-flex gap-2 w-100\">
            <button type=\"submit\" class=\"btn btn-primary\">
                <i class=\"fas fa-search\"></i>
            </button>
            <a href=\"{{ route('{$this->getRouteResourceName()}.index') }}\" class=\"btn btn-secondary\">
                <i class=\"fas fa-times\"></i>
            </a>
        </div>
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