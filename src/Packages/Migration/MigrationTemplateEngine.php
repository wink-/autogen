<?php

declare(strict_types=1);

namespace AutoGen\Packages\Migration;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;
use AutoGen\Common\Templates\TemplateEngine;
use AutoGen\Common\Exceptions\GenerationException;

class MigrationTemplateEngine
{
    /**
     * The base template engine instance.
     *
     * @var TemplateEngine
     */
    protected TemplateEngine $templateEngine;

    /**
     * Database type to Laravel column type mappings.
     *
     * @var array
     */
    protected array $columnTypeMappings = [
        // MySQL/MariaDB
        'tinyint' => 'tinyInteger',
        'smallint' => 'smallInteger',
        'mediumint' => 'mediumInteger',
        'int' => 'integer',
        'bigint' => 'bigInteger',
        'decimal' => 'decimal',
        'float' => 'float',
        'double' => 'double',
        'varchar' => 'string',
        'char' => 'char',
        'text' => 'text',
        'mediumtext' => 'mediumText',
        'longtext' => 'longText',
        'json' => 'json',
        'date' => 'date',
        'datetime' => 'dateTime',
        'timestamp' => 'timestamp',
        'time' => 'time',
        'year' => 'year',
        'binary' => 'binary',
        'varbinary' => 'binary',
        'blob' => 'binary',
        'mediumblob' => 'binary',
        'longblob' => 'binary',
        'enum' => 'enum',
        'set' => 'set',
        'geometry' => 'geometry',
        'point' => 'point',
        'linestring' => 'linestring',
        'polygon' => 'polygon',
        
        // PostgreSQL
        'serial' => 'increments',
        'bigserial' => 'bigIncrements',
        'boolean' => 'boolean',
        'bool' => 'boolean',
        'bytea' => 'binary',
        'jsonb' => 'jsonb',
        'uuid' => 'uuid',
        'inet' => 'ipAddress',
        'macaddr' => 'macAddress',
        'cidr' => 'string',
        'array' => 'json',
        
        // SQLite
        'integer' => 'integer',
        'real' => 'float',
        'numeric' => 'decimal',
        'blob' => 'binary',
        
        // SQL Server
        'bit' => 'boolean',
        'tinyint' => 'tinyInteger',
        'smallint' => 'smallInteger',
        'int' => 'integer',
        'bigint' => 'bigInteger',
        'money' => 'decimal',
        'smallmoney' => 'decimal',
        'real' => 'float',
        'nvarchar' => 'string',
        'nchar' => 'char',
        'ntext' => 'text',
        'uniqueidentifier' => 'uuid',
        'datetime2' => 'dateTime',
        'datetimeoffset' => 'dateTimeTz',
        'smalldatetime' => 'dateTime',
        'image' => 'binary',
        'varbinary' => 'binary',
    ];

    /**
     * Create a new migration template engine instance.
     */
    public function __construct(TemplateEngine $templateEngine)
    {
        $this->templateEngine = $templateEngine;
    }

    /**
     * Generate migration content for a table.
     */
    public function generateMigrationContent(
        string $migrationName,
        string $table,
        array $tableStructure,
        array $schema,
        array $config
    ): string {
        $variables = [
            'migration_name' => $migrationName,
            'table_name' => $table,
            'table_structure' => $tableStructure,
            'schema' => $schema,
            'config' => $config,
            'up_content' => $this->generateUpMethodContent($table, $tableStructure, $config),
            'down_content' => $this->generateDownMethodContent($table, $config),
        ];

        return $this->templateEngine->render($this->getStubContent('migration'), $variables);
    }

    /**
     * Generate foreign key migration content.
     */
    public function generateForeignKeyMigrationContent(
        string $migrationName,
        string $table,
        array $foreignKeys,
        array $config
    ): string {
        $variables = [
            'migration_name' => $migrationName,
            'table_name' => $table,
            'foreign_keys' => $foreignKeys,
            'up_content' => $this->generateForeignKeyUpContent($table, $foreignKeys),
            'down_content' => $this->generateForeignKeyDownContent($table, $foreignKeys),
        ];

        return $this->templateEngine->render($this->getStubContent('foreign_key_migration'), $variables);
    }

    /**
     * Generate rollback migration content.
     */
    public function generateRollbackMigrationContent(
        string $migrationName,
        string $table,
        array $config
    ): string {
        $variables = [
            'migration_name' => $migrationName,
            'table_name' => $table,
            'up_content' => $this->generateRollbackUpContent($table),
            'down_content' => $this->generateRollbackDownContent($table),
        ];

        return $this->templateEngine->render($this->getStubContent('rollback_migration'), $variables);
    }

    /**
     * Generate up method content for table creation.
     */
    protected function generateUpMethodContent(string $table, array $tableStructure, array $config): string
    {
        $lines = [];
        $lines[] = "Schema::create('{$table}', function (Blueprint \$table) {";

        // Add columns
        foreach ($tableStructure['columns'] as $column) {
            $lines[] = $this->generateColumnDefinition($column, $tableStructure);
        }

        // Add indexes (but not foreign keys if they're separate)
        if ($config['with_indexes']) {
            foreach ($tableStructure['indexes'] as $index) {
                $lines[] = $this->generateIndexDefinition($index);
            }
        }

        // Add foreign keys (only if not separating them)
        if ($config['with_foreign_keys'] && !$this->shouldSeparateForeignKeys($tableStructure)) {
            foreach ($tableStructure['foreign_keys'] as $fk) {
                $lines[] = $this->generateForeignKeyDefinition($fk);
            }
        }

        // Add table options
        $this->addTableOptions($lines, $tableStructure);

        $lines[] = "});";

        return implode("\n            ", $lines);
    }

    /**
     * Generate down method content for table creation.
     */
    protected function generateDownMethodContent(string $table, array $config): string
    {
        return "Schema::dropIfExists('{$table}');";
    }

    /**
     * Generate column definition.
     */
    protected function generateColumnDefinition(array $column, array $tableStructure): string
    {
        $name = $column['name'];
        $type = $this->mapColumnType($column);
        $modifiers = $this->generateColumnModifiers($column, $tableStructure);

        $definition = "\$table->{$type}('{$name}'";

        // Add parameters for specific column types
        if (in_array($type, ['string', 'char']) && $column['length']) {
            $definition .= ", {$column['length']}";
        } elseif (in_array($type, ['decimal', 'float', 'double']) && $column['precision']) {
            $precision = $column['precision'] ?? 8;
            $scale = $column['scale'] ?? 2;
            $definition .= ", {$precision}, {$scale}";
        } elseif ($type === 'enum' && isset($column['enum_values'])) {
            $values = implode("', '", $column['enum_values']);
            $definition .= ", ['{$values}']";
        }

        $definition .= ")";

        // Add modifiers
        if (!empty($modifiers)) {
            $definition .= $modifiers;
        }

        return $definition . ";";
    }

    /**
     * Map database column type to Laravel migration method.
     */
    protected function mapColumnType(array $column): string
    {
        $type = strtolower($column['type']);
        
        // Handle special cases
        if ($type === 'tinyint' && isset($column['length']) && $column['length'] == 1) {
            return 'boolean';
        }

        if ($type === 'int' && $column['auto_increment'] ?? false) {
            return 'increments';
        }

        if ($type === 'bigint' && $column['auto_increment'] ?? false) {
            return 'bigIncrements';
        }

        return $this->columnTypeMappings[$type] ?? 'string';
    }

    /**
     * Generate column modifiers.
     */
    protected function generateColumnModifiers(array $column, array $tableStructure): string
    {
        $modifiers = [];

        // Nullable
        if ($column['nullable'] && !$this->isPrimaryKey($column['name'], $tableStructure)) {
            $modifiers[] = 'nullable()';
        }

        // Default value
        if ($column['default'] !== null && $column['default'] !== '') {
            $default = $this->formatDefaultValue($column['default'], $column['type']);
            $modifiers[] = "default({$default})";
        }

        // Unsigned (MySQL specific)
        if (($column['unsigned'] ?? false) && in_array($column['type'], ['int', 'bigint', 'smallint', 'tinyint'])) {
            $modifiers[] = 'unsigned()';
        }

        // Auto increment (handled in type mapping mostly)
        if (($column['auto_increment'] ?? false) && !str_contains($this->mapColumnType($column), 'increment')) {
            $modifiers[] = 'autoIncrement()';
        }

        // Comment
        if (!empty($column['comment'])) {
            $comment = addslashes($column['comment']);
            $modifiers[] = "comment('{$comment}')";
        }

        return empty($modifiers) ? '' : '->' . implode('->', $modifiers);
    }

    /**
     * Check if column is primary key.
     */
    protected function isPrimaryKey(string $columnName, array $tableStructure): bool
    {
        $primaryKey = $tableStructure['primary_key'];
        
        if (!$primaryKey) {
            return false;
        }

        return in_array($columnName, $primaryKey['columns']);
    }

    /**
     * Format default value for migration.
     */
    protected function formatDefaultValue($value, string $type): string
    {
        if ($value === 'CURRENT_TIMESTAMP' || $value === 'now()') {
            return "DB::raw('CURRENT_TIMESTAMP')";
        }

        if (is_string($value) && !is_numeric($value)) {
            return "'" . addslashes($value) . "'";
        }

        if ($value === 'true' || $value === '1' && $type === 'boolean') {
            return 'true';
        }

        if ($value === 'false' || $value === '0' && $type === 'boolean') {
            return 'false';
        }

        return (string) $value;
    }

    /**
     * Generate index definition.
     */
    protected function generateIndexDefinition(array $index): string
    {
        $columns = is_array($index['columns']) ? $index['columns'] : [$index['columns']];
        $columnsList = "'" . implode("', '", $columns) . "'";

        if ($index['unique']) {
            if (count($columns) === 1) {
                return "\$table->unique('{$columns[0]}');";
            } else {
                return "\$table->unique([{$columnsList}], '{$index['name']}');";
            }
        } else {
            if (count($columns) === 1) {
                return "\$table->index('{$columns[0]}');";
            } else {
                return "\$table->index([{$columnsList}], '{$index['name']}');";
            }
        }
    }

    /**
     * Generate foreign key definition.
     */
    protected function generateForeignKeyDefinition(array $foreignKey): string
    {
        $column = $foreignKey['column'];
        $foreignTable = $foreignKey['foreign_table'];
        $foreignColumn = $foreignKey['foreign_column'];
        
        $definition = "\$table->foreign('{$column}')->references('{$foreignColumn}')->on('{$foreignTable}')";

        if ($foreignKey['on_delete'] !== 'RESTRICT') {
            $onDelete = strtolower($foreignKey['on_delete']);
            if ($onDelete === 'set null') {
                $definition .= "->onDelete('set null')";
            } elseif ($onDelete === 'cascade') {
                $definition .= "->onDelete('cascade')";
            }
        }

        if ($foreignKey['on_update'] !== 'RESTRICT') {
            $onUpdate = strtolower($foreignKey['on_update']);
            if ($onUpdate === 'cascade') {
                $definition .= "->onUpdate('cascade')";
            }
        }

        return $definition . ";";
    }

    /**
     * Add table options to migration.
     */
    protected function addTableOptions(array &$lines, array $tableStructure): void
    {
        // Engine (MySQL)
        if (!empty($tableStructure['engine']) && $tableStructure['engine'] !== 'InnoDB') {
            $lines[] = "\$table->engine = '{$tableStructure['engine']}';";
        }

        // Charset (MySQL)
        if (!empty($tableStructure['charset'])) {
            $lines[] = "\$table->charset = '{$tableStructure['charset']}';";
        }

        // Collation (MySQL)
        if (!empty($tableStructure['collation'])) {
            $lines[] = "\$table->collation = '{$tableStructure['collation']}';";
        }

        // Comment
        if (!empty($tableStructure['comment'])) {
            $comment = addslashes($tableStructure['comment']);
            $lines[] = "\$table->comment('{$comment}');";
        }
    }

    /**
     * Generate foreign key up content.
     */
    protected function generateForeignKeyUpContent(string $table, array $foreignKeys): string
    {
        $lines = [];
        $lines[] = "Schema::table('{$table}', function (Blueprint \$table) {";

        foreach ($foreignKeys as $fk) {
            $lines[] = "    " . $this->generateForeignKeyDefinition($fk);
        }

        $lines[] = "});";

        return implode("\n            ", $lines);
    }

    /**
     * Generate foreign key down content.
     */
    protected function generateForeignKeyDownContent(string $table, array $foreignKeys): string
    {
        $lines = [];
        $lines[] = "Schema::table('{$table}', function (Blueprint \$table) {";

        foreach ($foreignKeys as $fk) {
            $lines[] = "    \$table->dropForeign(['{$fk['column']}']);";
        }

        $lines[] = "});";

        return implode("\n            ", $lines);
    }

    /**
     * Generate rollback up content.
     */
    protected function generateRollbackUpContent(string $table): string
    {
        return "Schema::dropIfExists('{$table}');";
    }

    /**
     * Generate rollback down content.
     */
    protected function generateRollbackDownContent(string $table): string
    {
        return "// Cannot reliably recreate table structure in rollback";
    }

    /**
     * Check if foreign keys should be separated.
     */
    protected function shouldSeparateForeignKeys(array $tableStructure): bool
    {
        // For now, always include foreign keys in main migration
        // This can be customized based on requirements
        return false;
    }

    /**
     * Get stub content for migration type.
     */
    protected function getStubContent(string $type): string
    {
        $stubPath = __DIR__ . "/Stubs/{$type}.stub";

        if (File::exists($stubPath)) {
            return File::get($stubPath);
        }

        // Return default stub content if file doesn't exist
        return $this->getDefaultStubContent($type);
    }

    /**
     * Get default stub content.
     */
    protected function getDefaultStubContent(string $type): string
    {
        switch ($type) {
            case 'migration':
                return $this->getDefaultMigrationStub();
            case 'foreign_key_migration':
                return $this->getDefaultForeignKeyMigrationStub();
            case 'rollback_migration':
                return $this->getDefaultRollbackMigrationStub();
            default:
                throw new GenerationException("Unknown migration stub type: {$type}");
        }
    }

    /**
     * Get default migration stub.
     */
    protected function getDefaultMigrationStub(): string
    {
        return '<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        {{ up_content }}
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        {{ down_content }}
    }
};';
    }

    /**
     * Get default foreign key migration stub.
     */
    protected function getDefaultForeignKeyMigrationStub(): string
    {
        return '<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        {{ up_content }}
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        {{ down_content }}
    }
};';
    }

    /**
     * Get default rollback migration stub.
     */
    protected function getDefaultRollbackMigrationStub(): string
    {
        return '<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        {{ up_content }}
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        {{ down_content }}
    }
};';
    }
}