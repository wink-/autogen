<?php

declare(strict_types=1);

namespace AutoGen\Common\Rollback;

use AutoGen\Common\Contracts\RollbackableGeneratorInterface;
use AutoGen\Common\Traits\HasRollbackSupport;
use AutoGen\Packages\Model\ModelGenerator;

/**
 * Model generator with rollback capabilities.
 */
class RollbackableModelGenerator extends ModelGenerator implements RollbackableGeneratorInterface
{
    use HasRollbackSupport;

    protected array $rollbackStrategies = ['file_backup', 'database_rollback'];

    /**
     * Generate model with rollback support.
     */
    public function generate(array $context): string
    {
        return $this->generateWithRollback($context);
    }

    /**
     * Perform the actual model generation.
     */
    protected function performGeneration(array $context): string
    {
        $modelName = $context['model_name'] ?? '';
        $tableName = $context['table_name'] ?? '';

        if (empty($modelName) || empty($tableName)) {
            throw new \InvalidArgumentException('Model name and table name are required');
        }

        try {
            // Step 1: Analyze database table
            $rollbackPoint1 = $this->createRollbackPoint('before_table_analysis', $context);
            
            $tableInfo = $this->executeWithRollbackProtection(function () use ($tableName) {
                return $this->analyzeTable($tableName);
            }, $context);

            // Step 2: Generate model content
            $rollbackPoint2 = $this->createRollbackPoint('before_model_generation', $context);
            
            $modelContent = $this->executeWithRollbackProtection(function () use ($context, $tableInfo) {
                return $this->generateModelContent($context, $tableInfo);
            }, $context);

            // Step 3: Write model file
            $rollbackPoint3 = $this->createRollbackPoint('before_file_write', $context);
            
            $this->executeWithRollbackProtection(function () use ($context, $modelContent) {
                $this->writeModelFile($context, $modelContent);
            }, $context);

            // Step 4: Generate related files if requested
            if ($context['generate_factory'] ?? false) {
                $this->executeWithRollbackProtection(function () use ($context) {
                    $this->generateFactory($context);
                }, $context);
            }

            if ($context['generate_migration'] ?? false) {
                $this->executeWithRollbackProtection(function () use ($context) {
                    $this->generateMigration($context);
                }, $context);
            }

            return $modelContent;
        } catch (\Exception $e) {
            // The HasRollbackSupport trait will handle automatic rollback
            throw $e;
        }
    }

    /**
     * Analyze database table.
     */
    protected function analyzeTable(string $tableName): array
    {
        // Use existing ModelGenerator functionality
        return [
            'columns' => $this->getTableColumns($tableName),
            'relationships' => $this->analyzeRelationships($tableName),
            'indexes' => $this->getTableIndexes($tableName),
        ];
    }

    /**
     * Generate model content.
     */
    protected function generateModelContent(array $context, array $tableInfo): string
    {
        $modelName = $context['model_name'];
        $tableName = $context['table_name'];
        $namespace = $context['namespace'] ?? 'App\\Models';

        $template = $this->selectModelTemplate($context);
        
        return $this->renderTemplate($template, [
            'namespace' => $namespace,
            'modelName' => $modelName,
            'tableName' => $tableName,
            'columns' => $tableInfo['columns'],
            'relationships' => $tableInfo['relationships'],
            'fillable' => $this->getFillableFields($tableInfo['columns']),
            'hidden' => $this->getHiddenFields($tableInfo['columns']),
            'casts' => $this->getCasts($tableInfo['columns']),
            'dates' => $this->getDateFields($tableInfo['columns']),
        ]);
    }

    /**
     * Write model file.
     */
    protected function writeModelFile(array $context, string $content): void
    {
        $modelName = $context['model_name'];
        $filePath = app_path("Models/{$modelName}.php");

        // Ensure directory exists
        $directory = dirname($filePath);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        file_put_contents($filePath, $content);
    }

    /**
     * Generate factory file.
     */
    protected function generateFactory(array $context): void
    {
        $modelName = $context['model_name'];
        $factoryName = "{$modelName}Factory";
        
        // This would integrate with the FactoryGenerator
        // For now, just create a placeholder
        $factoryPath = database_path("factories/{$factoryName}.php");
        $factoryContent = $this->generateFactoryContent($context);
        
        file_put_contents($factoryPath, $factoryContent);
    }

    /**
     * Generate migration file.
     */
    protected function generateMigration(array $context): void
    {
        $tableName = $context['table_name'];
        $migrationName = "create_{$tableName}_table";
        
        // This would integrate with the MigrationGenerator
        // For now, just create a placeholder
        $timestamp = now()->format('Y_m_d_His');
        $migrationPath = database_path("migrations/{$timestamp}_{$migrationName}.php");
        $migrationContent = $this->generateMigrationContent($context);
        
        file_put_contents($migrationPath, $migrationContent);
    }

    /**
     * Select appropriate model template.
     */
    protected function selectModelTemplate(array $context): string
    {
        if ($context['enhanced_model'] ?? false) {
            return 'model-enhanced';
        }

        if ($context['with_relationships'] ?? false) {
            return 'model-with-relationships';
        }

        if ($context['minimal_model'] ?? false) {
            return 'model-minimal';
        }

        return 'model';
    }

    /**
     * Get files that should be backed up for model generation.
     */
    protected function getFilesToBackup(array $context): array
    {
        $files = parent::getFilesToBackup($context);

        // Add model-specific files
        if (isset($context['model_name'])) {
            $modelName = $context['model_name'];
            
            // Model file
            $files[] = app_path("Models/{$modelName}.php");
            
            // Factory file if it exists
            $factoryFile = database_path("factories/{$modelName}Factory.php");
            if (file_exists($factoryFile)) {
                $files[] = $factoryFile;
            }
            
            // Test files if they exist
            $testFiles = [
                base_path("tests/Unit/{$modelName}Test.php"),
                base_path("tests/Feature/{$modelName}Test.php"),
            ];
            
            foreach ($testFiles as $testFile) {
                if (file_exists($testFile)) {
                    $files[] = $testFile;
                }
            }
        }

        return $files;
    }

    /**
     * Placeholder methods (these would be implemented properly in the full system).
     */
    protected function getTableColumns(string $tableName): array
    {
        // Implementation would use DatabaseIntrospector
        return [];
    }

    protected function analyzeRelationships(string $tableName): array
    {
        // Implementation would use RelationshipAnalyzer
        return [];
    }

    protected function getTableIndexes(string $tableName): array
    {
        // Implementation would analyze table indexes
        return [];
    }

    protected function renderTemplate(string $template, array $data): string
    {
        // Implementation would use the template engine
        return "<?php\n\n// Generated model content for {$data['modelName']}\n";
    }

    protected function getFillableFields(array $columns): array
    {
        // Implementation would determine fillable fields
        return [];
    }

    protected function getHiddenFields(array $columns): array
    {
        // Implementation would determine hidden fields
        return [];
    }

    protected function getCasts(array $columns): array
    {
        // Implementation would determine casts
        return [];
    }

    protected function getDateFields(array $columns): array
    {
        // Implementation would determine date fields
        return [];
    }

    protected function generateFactoryContent(array $context): string
    {
        // Implementation would generate factory content
        return "<?php\n\n// Generated factory content\n";
    }

    protected function generateMigrationContent(array $context): string
    {
        // Implementation would generate migration content
        return "<?php\n\n// Generated migration content\n";
    }
}