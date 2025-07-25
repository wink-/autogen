<?php

declare(strict_types=1);

namespace AutoGen\Packages\Datatable;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ExportGenerator
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
     * Generate export-related files.
     */
    public function generate(): array
    {
        $this->generateExportClass();
        
        if ($this->options['backgroundJobs'] ?? false) {
            $this->generateExportJob();
            $this->generateExportNotification();
        }
        
        return $this->generatedFiles;
    }

    /**
     * Generate the export class.
     */
    protected function generateExportClass(): void
    {
        $path = $this->getExportPath();
        $this->ensureDirectoryExists(dirname($path));

        $stub = $this->getExportStub();
        $content = $this->replaceStubVariables($stub);

        if (File::exists($path) && !($this->options['force'] ?? false)) {
            throw new \Exception("Export class already exists at: {$path}");
        }

        File::put($path, $content);
        $this->generatedFiles[] = $path;
    }

    /**
     * Generate the export job for background processing.
     */
    protected function generateExportJob(): void
    {
        $path = $this->getExportJobPath();
        $this->ensureDirectoryExists(dirname($path));

        $stub = $this->getExportJobStub();
        $content = $this->replaceStubVariables($stub);

        if (File::exists($path) && !($this->options['force'] ?? false)) {
            throw new \Exception("Export job already exists at: {$path}");
        }

        File::put($path, $content);
        $this->generatedFiles[] = $path;
    }

    /**
     * Generate the export notification.
     */
    protected function generateExportNotification(): void
    {
        $path = $this->getExportNotificationPath();
        $this->ensureDirectoryExists(dirname($path));

        $stub = $this->getExportNotificationStub();
        $content = $this->replaceStubVariables($stub);

        if (File::exists($path) && !($this->options['force'] ?? false)) {
            throw new \Exception("Export notification already exists at: {$path}");
        }

        File::put($path, $content);
        $this->generatedFiles[] = $path;
    }

    /**
     * Get the export stub file.
     */
    protected function getExportStub(): string
    {
        $customStubPath = config('autogen.custom_stubs_path');
        
        if ($customStubPath && File::exists($customStubPath . '/datatable/export.stub')) {
            return File::get($customStubPath . '/datatable/export.stub');
        }

        return File::get(__DIR__ . '/Stubs/export.stub');
    }

    /**
     * Get the export job stub file.
     */
    protected function getExportJobStub(): string
    {
        $customStubPath = config('autogen.custom_stubs_path');
        
        if ($customStubPath && File::exists($customStubPath . '/datatable/export.job.stub')) {
            return File::get($customStubPath . '/datatable/export.job.stub');
        }

        return File::get(__DIR__ . '/Stubs/export.job.stub');
    }

    /**
     * Get the export notification stub file.
     */
    protected function getExportNotificationStub(): string
    {
        $customStubPath = config('autogen.custom_stubs_path');
        
        if ($customStubPath && File::exists($customStubPath . '/datatable/export.notification.stub')) {
            return File::get($customStubPath . '/datatable/export.notification.stub');
        }

        return File::get(__DIR__ . '/Stubs/export.notification.stub');
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
        $exportNamespace = $this->getExportNamespace();
        $jobNamespace = $this->getJobNamespace();
        $notificationNamespace = $this->getNotificationNamespace();
        $exportName = $this->getExportName();
        $jobName = $this->getJobName();
        $notificationName = $this->getNotificationName();
        $modelVariable = $this->getModelVariable();
        $modelVariablePlural = Str::plural($modelVariable);

        $replacements = [
            'namespace' => $exportNamespace,
            'jobNamespace' => $jobNamespace,
            'notificationNamespace' => $notificationNamespace,
            'modelNamespace' => $modelNamespace,
            'modelClass' => $modelClass,
            'exportName' => $exportName,
            'jobName' => $jobName,
            'notificationName' => $notificationName,
            'modelVariable' => $modelVariable,
            'modelVariablePlural' => $modelVariablePlural,
            'exportMethods' => $this->getExportMethods(),
            'headings' => $this->getHeadings(),
            'mapping' => $this->getMapping(),
            'styles' => $this->getStyles(),
            'jobHandle' => $this->getJobHandle(),
            'notificationContent' => $this->getNotificationContent(),
            'pdfView' => $this->getPdfView(),
            'chunkSize' => $this->getChunkSize(),
            'memoryOptimization' => $this->getMemoryOptimization(),
        ];

        return $replacements;
    }

    /**
     * Get export methods implementation.
     */
    protected function getExportMethods(): string
    {
        return "
    /**
     * Export collection data.
     */
    public function collection()
    {
        return \$this->data;
    }

    /**
     * Export with chunking for large datasets.
     */
    public function query()
    {
        if (\$this->data instanceof \\Illuminate\\Database\\Eloquent\\Collection) {
            return collect(\$this->data);
        }
        
        return \$this->data;
    }

    /**
     * Configure chunk size for memory optimization.
     */
    public function chunkSize(): int
    {
        return {$this->getChunkSize()};
    }

    /**
     * Configure batch size for processing.
     */
    public function batchSize(): int
    {
        return 1000;
    }

    /**
     * Handle memory optimization.
     */
    public function registerEvents(): array
    {
        return [
            BeforeExport::class => function(BeforeExport \$event) {
                \$totalRows = \$this->data->count();
                \$event->writer->getProperties()->setCreator('AutoGen DataTable');
                \$event->writer->getProperties()->setTitle('{$this->getModelClass()} Export');
                \$event->writer->getProperties()->setDescription(\"Export of {\$totalRows} {$this->getModelVariable()} records\");
            },
        ];
    }";
    }

    /**
     * Get headings for the export.
     */
    protected function getHeadings(): string
    {
        return "
    /**
     * Define export headings.
     */
    public function headings(): array
    {
        return [
            'ID',
            'Name',
            'Email',
            'Status',
            'Created At',
            'Updated At',
        ];
    }";
    }

    /**
     * Get mapping for the export data.
     */
    protected function getMapping(): string
    {
        $modelVariable = $this->getModelVariable();

        return "
    /**
     * Map the data for export.
     */
    public function map(\${$modelVariable}): array
    {
        return [
            \${$modelVariable}->id,
            \${$modelVariable}->name,
            \${$modelVariable}->email,
            \${$modelVariable}->status ?? 'active',
            \${$modelVariable}->created_at?->format('Y-m-d H:i:s'),
            \${$modelVariable}->updated_at?->format('Y-m-d H:i:s'),
        ];
    }";
    }

    /**
     * Get styles for Excel export.
     */
    protected function getStyles(): string
    {
        return "
    /**
     * Configure styles for Excel export.
     */
    public function styles(Worksheet \$sheet)
    {
        return [
            // Style the first row as bold text
            1 => ['font' => ['bold' => true]],
            
            // Style the header row
            'A1:F1' => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FFE2E2E2'],
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['argb' => 'FF000000'],
                    ],
                ],
            ],
        ];
    }

    /**
     * Configure column widths.
     */
    public function columnWidths(): array
    {
        return [
            'A' => 10, // ID
            'B' => 25, // Name
            'C' => 30, // Email
            'D' => 15, // Status
            'E' => 20, // Created At
            'F' => 20, // Updated At
        ];
    }";
    }

    /**
     * Get job handle implementation.
     */
    protected function getJobHandle(): string
    {
        $modelClass = $this->getModelClass();
        $exportName = $this->getExportName();
        $notificationName = $this->getNotificationName();

        return "
    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            \$filename = '{$this->getModelVariable()}s_' . now()->format('Y_m_d_H_i_s') . '.' . \$this->format;
            \$filePath = 'exports/' . \$filename;

            // Create the export
            \$export = new {$exportName}(\$this->data);
            
            switch (\$this->format) {
                case 'excel':
                    Excel::store(\$export, \$filePath, 'public');
                    break;
                case 'csv':
                    Excel::store(\$export, \$filePath, 'public', \\Maatwebsite\\Excel\\Excel::CSV);
                    break;
                case 'pdf':
                    \$pdf = PDF::loadView('exports.{$this->getModelVariable()}', ['data' => \$this->data]);
                    Storage::disk('public')->put(\$filePath, \$pdf->output());
                    break;
            }

            // Send notification to user
            \$this->user->notify(new {$notificationName}(\$filename, \$filePath, true));

        } catch (\\Exception \$e) {
            // Log the error
            Log::error('Export job failed', [
                'user_id' => \$this->user->id,
                'format' => \$this->format,
                'data_count' => count(\$this->data),
                'error' => \$e->getMessage()
            ]);

            // Notify user of failure
            \$this->user->notify(new {$notificationName}(null, null, false, \$e->getMessage()));
        }
    }

    /**
     * Handle job failure.
     */
    public function failed(\\Throwable \$exception): void
    {
        Log::error('Export job failed with exception', [
            'user_id' => \$this->user->id,
            'format' => \$this->format,
            'exception' => \$exception->getMessage()
        ]);

        \$this->user->notify(new {$notificationName}(null, null, false, \$exception->getMessage()));
    }";
    }

    /**
     * Get notification content implementation.
     */
    protected function getNotificationContent(): string
    {
        $modelClass = $this->getModelClass();

        return "
    /**
     * Get the notification's delivery channels.
     */
    public function via(\$notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(\$notifiable): MailMessage
    {
        if (\$this->success) {
            return (new MailMessage)
                ->subject('{$modelClass} Export Completed')
                ->greeting('Hello!')
                ->line('Your {$modelClass} export has been completed successfully.')
                ->line('File: ' . \$this->filename)
                ->action('Download Export', Storage::disk('public')->url(\$this->filePath))
                ->line('This download link will expire in 24 hours.')
                ->line('Thank you for using our application!');
        } else {
            return (new MailMessage)
                ->subject('{$modelClass} Export Failed')
                ->greeting('Hello!')
                ->line('Unfortunately, your {$modelClass} export has failed.')
                ->line('Error: ' . (\$this->error ?? 'Unknown error occurred'))
                ->line('Please try again or contact support if the problem persists.')
                ->line('Thank you for using our application!');
        }
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(\$notifiable): array
    {
        return [
            'type' => 'export',
            'model' => '{$modelClass}',
            'success' => \$this->success,
            'filename' => \$this->filename,
            'file_path' => \$this->filePath,
            'error' => \$this->error,
            'created_at' => now()->toISOString(),
        ];
    }";
    }

    /**
     * Get PDF view path.
     */
    protected function getPdfView(): string
    {
        return "exports.{$this->getModelVariable()}";
    }

    /**
     * Get chunk size for processing.
     */
    protected function getChunkSize(): int
    {
        return 1000;
    }

    /**
     * Get memory optimization settings.
     */
    protected function getMemoryOptimization(): string
    {
        return "
    /**
     * Configure memory usage optimization.
     */
    public function getCsvSettings(): array
    {
        return [
            'use_bom' => false,
            'output_encoding' => 'UTF-8',
            'delimiter' => ',',
            'enclosure' => '\"',
            'line_ending' => '\\r\\n',
        ];
    }

    /**
     * Configure temporary file handling.
     */
    public function useDisk(): string
    {
        return 'local';
    }

    /**
     * Configure queue processing.
     */
    public function queue(): string
    {
        return 'exports';
    }";
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
     * Get the export namespace.
     */
    protected function getExportNamespace(): string
    {
        $namespace = 'App\\Exports';

        $modelPath = str_replace('/', '\\', $this->modelName);
        if (str_contains($modelPath, '\\')) {
            $parts = explode('\\', $modelPath);
            array_pop($parts);
            $namespace .= '\\' . implode('\\', $parts);
        }

        return $namespace;
    }

    /**
     * Get the job namespace.
     */
    protected function getJobNamespace(): string
    {
        $namespace = 'App\\Jobs';

        $modelPath = str_replace('/', '\\', $this->modelName);
        if (str_contains($modelPath, '\\')) {
            $parts = explode('\\', $modelPath);
            array_pop($parts);
            $namespace .= '\\' . implode('\\', $parts);
        }

        return $namespace;
    }

    /**
     * Get the notification namespace.
     */
    protected function getNotificationNamespace(): string
    {
        $namespace = 'App\\Notifications';

        $modelPath = str_replace('/', '\\', $this->modelName);
        if (str_contains($modelPath, '\\')) {
            $parts = explode('\\', $modelPath);
            array_pop($parts);
            $namespace .= '\\' . implode('\\', $parts);
        }

        return $namespace;
    }

    /**
     * Get the export class name.
     */
    protected function getExportName(): string
    {
        return $this->getModelClass() . 'Export';
    }

    /**
     * Get the job class name.
     */
    protected function getJobName(): string
    {
        return 'Export' . $this->getModelClass() . 'Job';
    }

    /**
     * Get the notification class name.
     */
    protected function getNotificationName(): string
    {
        return $this->getModelClass() . 'ExportCompleted';
    }

    /**
     * Get the model variable name.
     */
    protected function getModelVariable(): string
    {
        return Str::camel($this->getModelClass());
    }

    /**
     * Get the export file path.
     */
    protected function getExportPath(): string
    {
        $path = app_path('Exports');

        $modelPath = str_replace('\\', '/', $this->modelName);
        if (str_contains($modelPath, '/')) {
            $parts = explode('/', $modelPath);
            array_pop($parts);
            $path .= '/' . implode('/', $parts);
        }

        return $path . '/' . $this->getExportName() . '.php';
    }

    /**
     * Get the export job file path.
     */
    protected function getExportJobPath(): string
    {
        $path = app_path('Jobs');

        $modelPath = str_replace('\\', '/', $this->modelName);
        if (str_contains($modelPath, '/')) {
            $parts = explode('/', $modelPath);
            array_pop($parts);
            $path .= '/' . implode('/', $parts);
        }

        return $path . '/' . $this->getJobName() . '.php';
    }

    /**
     * Get the export notification file path.
     */
    protected function getExportNotificationPath(): string
    {
        $path = app_path('Notifications');

        $modelPath = str_replace('\\', '/', $this->modelName);
        if (str_contains($modelPath, '/')) {
            $parts = explode('/', $modelPath);
            array_pop($parts);
            $path .= '/' . implode('/', $parts);
        }

        return $path . '/' . $this->getNotificationName() . '.php';
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