<?php

declare(strict_types=1);

namespace AutoGen\Packages\Views\Generators;

use Illuminate\Support\Str;

class PlainCssGenerator extends BaseFrameworkGenerator
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

    public function generateCss(): string
    {
        return "/* AutoGen CRUD Styles */
.autogen-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.autogen-card {
    background: #fff;
    border: 1px solid #e1e5e9;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    overflow: hidden;
}

.autogen-card-header {
    background: #f8f9fa;
    padding: 20px;
    border-bottom: 1px solid #e1e5e9;
}

.autogen-card-body {
    padding: 20px;
}

.autogen-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.autogen-title {
    font-size: 1.75rem;
    font-weight: 600;
    color: #2d3748;
    margin: 0 0 5px 0;
}

.autogen-subtitle {
    color: #718096;
    margin: 0;
    font-size: 0.875rem;
}

.autogen-button {
    display: inline-flex;
    align-items: center;
    padding: 8px 16px;
    border: 1px solid transparent;
    border-radius: 6px;
    font-size: 0.875rem;
    font-weight: 500;
    text-decoration: none;
    cursor: pointer;
    transition: all 0.2s;
}

.autogen-button:hover {
    text-decoration: none;
}

.autogen-button-primary {
    background: #4f46e5;
    color: white;
    border-color: #4f46e5;
}

.autogen-button-primary:hover {
    background: #4338ca;
    border-color: #4338ca;
    color: white;
}

.autogen-button-secondary {
    background: #6b7280;
    color: white;
    border-color: #6b7280;
}

.autogen-button-secondary:hover {
    background: #4b5563;
    border-color: #4b5563;
    color: white;
}

.autogen-button-danger {
    background: #dc2626;
    color: white;
    border-color: #dc2626;
}

.autogen-button-danger:hover {
    background: #b91c1c;
    border-color: #b91c1c;
    color: white;
}

.autogen-button-outline {
    background: transparent;
    color: #374151;
    border-color: #d1d5db;
}

.autogen-button-outline:hover {
    background: #f9fafb;
    color: #374151;
}

.autogen-button-group {
    display: flex;
    gap: 8px;
}

.autogen-icon {
    width: 16px;
    height: 16px;
    margin-right: 8px;
}

.autogen-alert {
    padding: 12px 16px;
    border-radius: 6px;
    margin-bottom: 20px;
}

.autogen-alert-success {
    background: #d1fae5;
    border: 1px solid #a7f3d0;
    color: #065f46;
}

.autogen-alert-error {
    background: #fee2e2;
    border: 1px solid #fecaca;
    color: #991b1b;
}

.autogen-table {
    width: 100%;
    border-collapse: collapse;
}

.autogen-table th,
.autogen-table td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #e5e7eb;
}

.autogen-table th {
    background: #f9fafb;
    font-weight: 600;
    color: #374151;
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.autogen-table tbody tr:hover {
    background: #f9fafb;
}

.autogen-form-group {
    margin-bottom: 20px;
}

.autogen-label {
    display: block;
    font-weight: 500;
    color: #374151;
    margin-bottom: 6px;
    font-size: 0.875rem;
}

.autogen-input,
.autogen-textarea,
.autogen-select {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 0.875rem;
    transition: border-color 0.2s;
}

.autogen-input:focus,
.autogen-textarea:focus,
.autogen-select:focus {
    outline: none;
    border-color: #4f46e5;
    box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
}

.autogen-input.error,
.autogen-textarea.error,
.autogen-select.error {
    border-color: #dc2626;
}

.autogen-textarea {
    resize: vertical;
    min-height: 80px;
}

.autogen-checkbox {
    display: flex;
    align-items: center;
}

.autogen-checkbox input[type=\"checkbox\"] {
    width: auto;
    margin-right: 8px;
}

.autogen-error-message {
    color: #dc2626;
    font-size: 0.75rem;
    margin-top: 4px;
}

.autogen-badge {
    display: inline-flex;
    align-items: center;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 500;
}

.autogen-badge-success {
    background: #d1fae5;
    color: #065f46;
}

.autogen-badge-danger {
    background: #fee2e2;
    color: #991b1b;
}

.autogen-empty-state {
    text-align: center;
    padding: 60px 20px;
    color: #6b7280;
}

.autogen-empty-icon {
    width: 48px;
    height: 48px;
    margin: 0 auto 16px;
    opacity: 0.5;
}

.autogen-filters {
    background: #f9fafb;
    padding: 20px;
    border-bottom: 1px solid #e5e7eb;
    display: none;
}

.autogen-filters.show {
    display: block;
}

.autogen-filters-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
    align-items: end;
}

.autogen-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
}

.autogen-modal.hidden {
    display: none;
}

.autogen-modal-content {
    background: white;
    border-radius: 8px;
    max-width: 400px;
    width: 90%;
    padding: 24px;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
}

.autogen-modal-header {
    text-align: center;
    margin-bottom: 16px;
}

.autogen-modal-icon {
    width: 48px;
    height: 48px;
    margin: 0 auto 12px;
    color: #dc2626;
}

.autogen-modal-title {
    font-size: 1.125rem;
    font-weight: 600;
    color: #111827;
    margin: 0;
}

.autogen-modal-body {
    text-align: center;
    margin-bottom: 24px;
    color: #6b7280;
}

.autogen-modal-footer {
    display: flex;
    justify-content: center;
    gap: 12px;
}

.autogen-detail-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 24px;
}

.autogen-detail-item {
    display: flex;
    flex-direction: column;
}

.autogen-detail-label {
    font-weight: 500;
    color: #6b7280;
    font-size: 0.875rem;
    margin-bottom: 4px;
}

.autogen-detail-value {
    color: #111827;
    font-size: 0.875rem;
}

.autogen-content {
    background: #f9fafb;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    padding: 12px;
    white-space: pre-wrap;
}

/* Responsive */
@media (max-width: 768px) {
    .autogen-container {
        padding: 10px;
    }
    
    .autogen-header {
        flex-direction: column;
        align-items: stretch;
        gap: 16px;
    }
    
    .autogen-button-group {
        justify-content: center;
    }
    
    .autogen-table {
        font-size: 0.75rem;
    }
    
    .autogen-table th,
    .autogen-table td {
        padding: 8px;
    }
    
    .autogen-filters-grid {
        grid-template-columns: 1fr;
    }
    
    .autogen-detail-grid {
        grid-template-columns: 1fr;
    }
}";
    }

    protected function generateIndexView(): string
    {
        $modelVariable = $this->getModelPluralVariable();
        $modelName = $this->modelBaseName;
        $routeName = $this->getRouteResourceName();
        
        $view = "@extends('{$this->layout}')

@section('title', '{$modelName} Management')

@push('styles')
<link href=\"/css/autogen/crud.css\" rel=\"stylesheet\">
@endpush

@section('content')
<div class=\"autogen-container\">
    <div class=\"autogen-card\">
        <!-- Header -->
        <div class=\"autogen-card-header\">
            <div class=\"autogen-header\">
                <div>
                    <h1 class=\"autogen-title\">{$modelName} Management</h1>
                    <p class=\"autogen-subtitle\">Manage your {$modelVariable}</p>
                </div>
                <div class=\"autogen-button-group\">
";

        if ($this->withSearch) {
            $view .= "                    <button type=\"button\" class=\"autogen-button autogen-button-outline\" onclick=\"toggleFilters()\">
                        🔍 Filters
                    </button>
";
        }

        $view .= "                    <a href=\"{{ route('{$routeName}.create') }}\" class=\"autogen-button autogen-button-primary\">
                        ➕ Add {$modelName}
                    </a>
                </div>
            </div>
        </div>

        <!-- Flash Messages -->
        @if(session('success'))
            <div class=\"autogen-alert autogen-alert-success\">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class=\"autogen-alert autogen-alert-error\">
                {{ session('error') }}
            </div>
        @endif
";

        if ($this->withSearch) {
            $view .= "
        <!-- Filters -->
        <div id=\"filters-section\" class=\"autogen-filters\">
            @include('{$this->getViewPath()}.filters')
        </div>
";
        }

        if ($this->withDatatable) {
            $view .= "
        <!-- DataTable -->
        <div class=\"autogen-card-body\">
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
<div id=\"deleteModal\" class=\"autogen-modal hidden\">
    <div class=\"autogen-modal-content\">
        <div class=\"autogen-modal-header\">
            <div class=\"autogen-modal-icon\">⚠️</div>
            <h3 class=\"autogen-modal-title\">Delete {$modelName}</h3>
        </div>
        <div class=\"autogen-modal-body\">
            <p>Are you sure you want to delete this {$modelVariable}? This action cannot be undone.</p>
        </div>
        <div class=\"autogen-modal-footer\">
            <button type=\"button\" class=\"autogen-button autogen-button-secondary\" onclick=\"closeDeleteModal()\">Cancel</button>
            <button type=\"button\" class=\"autogen-button autogen-button-danger\" id=\"deleteConfirm\">Delete</button>
        </div>
    </div>
</div>
";
        }

        $view .= "

<script>
";

        if ($this->withSearch) {
            $view .= "
function toggleFilters() {
    const filtersSection = document.getElementById('filters-section');
    filtersSection.classList.toggle('show');
}
";
        }

        if ($this->withModals) {
            $view .= "
let deleteForm = null;

function confirmDelete(form) {
    deleteForm = form;
    document.getElementById('deleteModal').classList.remove('hidden');
    return false;
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
        pageLength: 25
    });
});
";
        }

        $view .= "
</script>
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

@push('styles')
<link href=\"/css/autogen/crud.css\" rel=\"stylesheet\">
@endpush

@section('content')
<div class=\"autogen-container\">
    <div style=\"max-width: 800px; margin: 0 auto;\">
        <!-- Header -->
        <div class=\"autogen-header\">
            <div>
                <h1 class=\"autogen-title\">Create {$modelName}</h1>
                <p class=\"autogen-subtitle\">Add a new {$modelVariable} to the system</p>
            </div>
            <a href=\"{{ route('{$routeName}.index') }}\" class=\"autogen-button autogen-button-secondary\">
                ← Back to List
            </a>
        </div>

        <!-- Form Card -->
        <div class=\"autogen-card\">
            <div class=\"autogen-card-body\">
                <form method=\"POST\" action=\"{{ route('{$routeName}.store') }}\">
                    @csrf
                    @include('{$this->getViewPath()}._form')
                    
                    <div style=\"display: flex; justify-content: flex-end; gap: 12px; margin-top: 24px; padding-top: 24px; border-top: 1px solid #e5e7eb;\">
                        <a href=\"{{ route('{$routeName}.index') }}\" class=\"autogen-button autogen-button-outline\">Cancel</a>
                        <button type=\"submit\" class=\"autogen-button autogen-button-primary\">Create {$modelName}</button>
                    </div>
                </form>
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

@push('styles')
<link href=\"/css/autogen/crud.css\" rel=\"stylesheet\">
@endpush

@section('content')
<div class=\"autogen-container\">
    <div style=\"max-width: 800px; margin: 0 auto;\">
        <!-- Header -->
        <div class=\"autogen-header\">
            <div>
                <h1 class=\"autogen-title\">Edit {$modelName}</h1>
                <p class=\"autogen-subtitle\">Update {$modelVariable} information</p>
            </div>
            <div class=\"autogen-button-group\">
                <a href=\"{{ route('{$routeName}.show', \${$modelVariable}) }}\" class=\"autogen-button autogen-button-outline\">
                    👁 View
                </a>
                <a href=\"{{ route('{$routeName}.index') }}\" class=\"autogen-button autogen-button-secondary\">
                    ← Back to List
                </a>
            </div>
        </div>

        <!-- Form Card -->
        <div class=\"autogen-card\">
            <div class=\"autogen-card-body\">
                <form method=\"POST\" action=\"{{ route('{$routeName}.update', \${$modelVariable}) }}\">
                    @csrf
                    @method('PATCH')
                    @include('{$this->getViewPath()}._form')
                    
                    <div style=\"display: flex; justify-content: flex-end; gap: 12px; margin-top: 24px; padding-top: 24px; border-top: 1px solid #e5e7eb;\">
                        <a href=\"{{ route('{$routeName}.show', \${$modelVariable}) }}\" class=\"autogen-button autogen-button-outline\">Cancel</a>
                        <button type=\"submit\" class=\"autogen-button autogen-button-primary\">Update {$modelName}</button>
                    </div>
                </form>
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

@push('styles')
<link href=\"/css/autogen/crud.css\" rel=\"stylesheet\">
@endpush

@section('content')
<div class=\"autogen-container\">
    <div style=\"max-width: 1000px; margin: 0 auto;\">
        <!-- Header -->
        <div class=\"autogen-header\">
            <div>
                <h1 class=\"autogen-title\">{$modelName} Details</h1>
                <p class=\"autogen-subtitle\">View {$modelVariable} information</p>
            </div>
            <div class=\"autogen-button-group\">
                <a href=\"{{ route('{$routeName}.edit', \${$modelVariable}) }}\" class=\"autogen-button autogen-button-primary\">
                    ✏️ Edit
                </a>
";

        if ($this->withModals) {
            $view .= "                <form method=\"POST\" action=\"{{ route('{$routeName}.destroy', \${$modelVariable}) }}\" style=\"display: inline;\" onsubmit=\"return confirmDelete(this)\">
                    @csrf
                    @method('DELETE')
                    <button type=\"submit\" class=\"autogen-button autogen-button-danger\">
                        🗑️ Delete
                    </button>
                </form>
";
        } else {
            $view .= "                <form method=\"POST\" action=\"{{ route('{$routeName}.destroy', \${$modelVariable}) }}\" style=\"display: inline;\" onsubmit=\"return confirm('Are you sure you want to delete this {$modelVariable}?')\">
                    @csrf
                    @method('DELETE')
                    <button type=\"submit\" class=\"autogen-button autogen-button-danger\">
                        🗑️ Delete
                    </button>
                </form>
";
        }

        $view .= "                <a href=\"{{ route('{$routeName}.index') }}\" class=\"autogen-button autogen-button-secondary\">
                    ← Back to List
                </a>
            </div>
        </div>

        <!-- Content Card -->
        <div class=\"autogen-card\">
            <div class=\"autogen-card-header\">
                <h3 style=\"margin: 0; font-size: 1.125rem; font-weight: 600;\">{$modelName} Information</h3>
            </div>
            
            <div class=\"autogen-card-body\">
                <div class=\"autogen-detail-grid\">
";

        // Add ID field
        $view .= "                    <div class=\"autogen-detail-item\">
                        <div class=\"autogen-detail-label\">ID</div>
                        <div class=\"autogen-detail-value\">{{ \${$modelVariable}->{$this->primaryKey} }}</div>
                    </div>
";

        // Add fillable fields
        foreach ($this->fillableFields as $field) {
            $label = $this->getFieldLabel($field);
            $fieldType = $this->getFieldType($field);
            
            $view .= "                    <div class=\"autogen-detail-item\">
                        <div class=\"autogen-detail-label\">{$label}</div>
                        <div class=\"autogen-detail-value\">";
            
            if ($fieldType === 'checkbox') {
                $view .= "
                            <span class=\"autogen-badge {{ \${$modelVariable}->{$field} ? 'autogen-badge-success' : 'autogen-badge-danger' }}\">
                                {{ \${$modelVariable}->{$field} ? 'Yes' : 'No' }}
                            </span>";
            } elseif ($fieldType === 'textarea') {
                $view .= "
                            <div class=\"autogen-content\">{{ \${$modelVariable}->{$field} ?? 'Not set' }}</div>";
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
        }

        // Add timestamps if they exist
        if (in_array('created_at', $this->columns)) {
            $view .= "                    <div class=\"autogen-detail-item\">
                        <div class=\"autogen-detail-label\">Created At</div>
                        <div class=\"autogen-detail-value\">{{ \${$modelVariable}->created_at->format('M j, Y g:i A') }}</div>
                    </div>
";
        }

        if (in_array('updated_at', $this->columns)) {
            $view .= "                    <div class=\"autogen-detail-item\">
                        <div class=\"autogen-detail-label\">Updated At</div>
                        <div class=\"autogen-detail-value\">{{ \${$modelVariable}->updated_at->format('M j, Y g:i A') }}</div>
                    </div>
";
        }

        $view .= "                </div>
            </div>
        </div>
    </div>
</div>
";

        if ($this->withModals) {
            $view .= "
<!-- Delete Confirmation Modal -->
<div id=\"deleteModal\" class=\"autogen-modal hidden\">
    <div class=\"autogen-modal-content\">
        <div class=\"autogen-modal-header\">
            <div class=\"autogen-modal-icon\">⚠️</div>
            <h3 class=\"autogen-modal-title\">Delete {$modelName}</h3>
        </div>
        <div class=\"autogen-modal-body\">
            <p>Are you sure you want to delete this {$modelVariable}? This action cannot be undone.</p>
        </div>
        <div class=\"autogen-modal-footer\">
            <button type=\"button\" class=\"autogen-button autogen-button-secondary\" onclick=\"closeDeleteModal()\">Cancel</button>
            <button type=\"button\" class=\"autogen-button autogen-button-danger\" id=\"deleteConfirm\">Delete</button>
        </div>
    </div>
</div>

<script>
let deleteForm = null;

function confirmDelete(form) {
    deleteForm = form;
    document.getElementById('deleteModal').classList.remove('hidden');
    return false;
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
</script>
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
            $errorClass = $type === 'checkbox' ? '' : " @error('{$field}') error @enderror";
            
            $view .= "<div class=\"autogen-form-group\">\n";
            $view .= "    <label for=\"{$field}\" class=\"autogen-label\">{$label}</label>\n";
            
            if ($type === 'textarea') {
                $view .= "    <textarea name=\"{$field}\" id=\"{$field}\" rows=\"4\" class=\"autogen-textarea{$errorClass}\" placeholder=\"{$placeholder}\" {$validationAttrs}>{{ old('{$field}', \${$modelVariable}->{$field} ?? '') }}</textarea>\n";
            } elseif ($type === 'checkbox') {
                $view .= "    <div class=\"autogen-checkbox\">\n";
                $view .= "        <input type=\"checkbox\" name=\"{$field}\" id=\"{$field}\" value=\"1\" {{ old('{$field}', \${$modelVariable}->{$field} ?? false) ? 'checked' : '' }} {$validationAttrs}>\n";
                $view .= "        <label for=\"{$field}\">{$label}</label>\n";
                $view .= "    </div>\n";
            } else {
                $view .= "    <input type=\"{$type}\" name=\"{$field}\" id=\"{$field}\" class=\"autogen-input{$errorClass}\" placeholder=\"{$placeholder}\" value=\"{{ old('{$field}', \${$modelVariable}->{$field} ?? '') }}\" {$validationAttrs}>\n";
            }
            
            $view .= "    @error('{$field}')\n";
            $view .= "        <div class=\"autogen-error-message\">{{ \$message }}</div>\n";
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

        $view = "<div class=\"autogen-card-body\" style=\"padding: 0; overflow-x: auto;\">
    <table class=\"autogen-table\">
        <thead>
            <tr>
";

        foreach ($columns as $column) {
            $label = $this->getFieldLabel($column);
            $view .= "                <th>{$label}</th>\n";
        }

        $view .= "                <th style=\"width: 150px;\">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse(\${$modelVariable} as \${$singleModelVariable})
                <tr>
";

        foreach ($columns as $column) {
            $fieldType = $this->getFieldType($column);
            
            $view .= "                    <td>";
            
            if ($fieldType === 'checkbox') {
                $view .= "
                        <span class=\"autogen-badge {{ \${$singleModelVariable}->{$column} ? 'autogen-badge-success' : 'autogen-badge-danger' }}\">
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

        $view .= "                    <td>
                        <div class=\"autogen-button-group\">
                            <a href=\"{{ route('{$routeName}.show', \${$singleModelVariable}) }}\" class=\"autogen-button autogen-button-outline\" style=\"padding: 4px 8px; font-size: 0.75rem;\" title=\"View\">
                                👁
                            </a>
                            <a href=\"{{ route('{$routeName}.edit', \${$singleModelVariable}) }}\" class=\"autogen-button autogen-button-outline\" style=\"padding: 4px 8px; font-size: 0.75rem;\" title=\"Edit\">
                                ✏️
                            </a>
";

        if ($this->withModals) {
            $view .= "                            <form method=\"POST\" action=\"{{ route('{$routeName}.destroy', \${$singleModelVariable}) }}\" style=\"display: inline;\" onsubmit=\"return confirmDelete(this)\">
                                @csrf
                                @method('DELETE')
                                <button type=\"submit\" class=\"autogen-button autogen-button-outline\" style=\"padding: 4px 8px; font-size: 0.75rem;\" title=\"Delete\">
                                    🗑️
                                </button>
                            </form>
";
        } else {
            $view .= "                            <form method=\"POST\" action=\"{{ route('{$routeName}.destroy', \${$singleModelVariable}) }}\" style=\"display: inline;\" onsubmit=\"return confirm('Are you sure you want to delete this {$singleModelVariable}?')\">
                                @csrf
                                @method('DELETE')
                                <button type=\"submit\" class=\"autogen-button autogen-button-outline\" style=\"padding: 4px 8px; font-size: 0.75rem;\" title=\"Delete\">
                                    🗑️
                                </button>
                            </form>
";
        }

        $view .= "                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan=\"" . (count($columns) + 1) . "\">
                        <div class=\"autogen-empty-state\">
                            <div class=\"autogen-empty-icon\">📦</div>
                            <h3 style=\"margin: 0 0 8px 0; font-size: 1.125rem;\">No {$modelVariable} found</h3>
                            <p style=\"margin: 0 0 16px 0;\">Get started by creating a new {$singleModelVariable}.</p>
                            <a href=\"{{ route('{$routeName}.create') }}\" class=\"autogen-button autogen-button-primary\">
                                ➕ Add {$this->modelBaseName}
                            </a>
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
    
    <!-- Pagination -->
    @if(method_exists(\${$modelVariable}, 'links'))
        <div style=\"padding: 16px; border-top: 1px solid #e5e7eb; background: #f9fafb;\">
            {{ \${$modelVariable}->links() }}
        </div>
    @endif
</div>";

        return $view;
    }

    protected function generateFiltersPartial(): string
    {
        $view = "<form method=\"GET\" action=\"{{ route('{$this->getRouteResourceName()}.index') }}\" class=\"autogen-filters-grid\">
    <!-- Search -->
    <div class=\"autogen-form-group\">
        <label for=\"search\" class=\"autogen-label\">Search</label>
        <input type=\"text\" name=\"search\" id=\"search\" class=\"autogen-input\" placeholder=\"Search...\" value=\"{{ request('search') }}\">
    </div>
";

        // Add status filter if exists
        if (in_array('status', $this->fillableFields)) {
            $view .= "
    <!-- Status -->
    <div class=\"autogen-form-group\">
        <label for=\"status\" class=\"autogen-label\">Status</label>
        <select name=\"status\" id=\"status\" class=\"autogen-select\">
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
    <div class=\"autogen-form-group\">
        <label for=\"date_from\" class=\"autogen-label\">Date From</label>
        <input type=\"date\" name=\"date_from\" id=\"date_from\" class=\"autogen-input\" value=\"{{ request('date_from') }}\">
    </div>
    
    <div class=\"autogen-form-group\">
        <label for=\"date_to\" class=\"autogen-label\">Date To</label>
        <input type=\"date\" name=\"date_to\" id=\"date_to\" class=\"autogen-input\" value=\"{{ request('date_to') }}\">
    </div>
";
        }

        $view .= "
    <!-- Actions -->
    <div class=\"autogen-form-group\">
        <div class=\"autogen-button-group\">
            <button type=\"submit\" class=\"autogen-button autogen-button-primary\">
                🔍 Filter
            </button>
            <a href=\"{{ route('{$this->getRouteResourceName()}.index') }}\" class=\"autogen-button autogen-button-secondary\">
                Clear
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