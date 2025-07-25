<?php

declare(strict_types=1);

namespace AutoGen\Packages\Migration;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Connection;
use AutoGen\Packages\Model\DatabaseIntrospector;
use AutoGen\Common\Exceptions\AutoGenException;

class DatabaseSchemaAnalyzer
{
    /**
     * The database introspector instance.
     *
     * @var DatabaseIntrospector
     */
    protected DatabaseIntrospector $introspector;

    /**
     * Create a new database schema analyzer instance.
     */
    public function __construct(DatabaseIntrospector $introspector)
    {
        $this->introspector = $introspector;
    }

    /**
     * Validate database connection.
     */
    public function validateConnection(string $connection): void
    {
        try {
            $db = DB::connection($connection);
            $db->getPdo();
        } catch (\Exception $e) {
            throw new AutoGenException("Invalid database connection: {$connection}. {$e->getMessage()}");
        }
    }

    /**
     * Get all tables from the database.
     */
    public function getAllTables(string $connection): array
    {
        return $this->introspector->getTables($connection);
    }

    /**
     * Analyze complete database schema.
     */
    public function analyzeSchema(string $connection, array $tables, array $config): array
    {
        $schema = [
            'connection' => $connection,
            'driver' => DB::connection($connection)->getDriverName(),
            'tables' => [],
            'relationships' => [],
            'dependencies' => [],
        ];

        foreach ($tables as $table) {
            $schema['tables'][$table] = $this->analyzeTable($connection, $table, $config);
        }

        // Analyze relationships across all tables
        $schema['relationships'] = $this->analyzeRelationships($connection, $tables);
        
        // Build dependency graph
        $schema['dependencies'] = $this->buildDependencyGraph($schema['tables']);

        return $schema;
    }

    /**
     * Analyze a single table structure.
     */
    public function analyzeTable(string $connection, string $table, array $config): array
    {
        $db = DB::connection($connection);
        $driver = $db->getDriverName();

        $structure = [
            'name' => $table,
            'driver' => $driver,
            'columns' => $this->introspector->getColumns($connection, $table),
            'primary_key' => $this->introspector->getPrimaryKey($connection, $table),
            'indexes' => $config['with_indexes'] ? $this->introspector->getIndexes($connection, $table) : [],
            'foreign_keys' => $config['with_foreign_keys'] ? $this->introspector->getForeignKeys($connection, $table) : [],
            'constraints' => $this->getConstraints($connection, $table),
            'engine' => $this->getTableEngine($connection, $table),
            'charset' => $this->getTableCharset($connection, $table),
            'collation' => $this->getTableCollation($connection, $table),
            'comment' => $this->getTableComment($connection, $table),
            'timestamps' => $this->introspector->hasTimestamps($connection, $table),
            'soft_deletes' => $this->introspector->hasSoftDeletes($connection, $table),
            'options' => $this->getTableOptions($connection, $table),
        ];

        // Add database-specific analysis
        switch ($driver) {
            case 'mysql':
            case 'mariadb':
                $structure = array_merge($structure, $this->analyzeMySqlTable($db, $table));
                break;
            case 'pgsql':
                $structure = array_merge($structure, $this->analyzePostgresTable($db, $table));
                break;
            case 'sqlite':
                $structure = array_merge($structure, $this->analyzeSqliteTable($db, $table));
                break;
            case 'sqlsrv':
                $structure = array_merge($structure, $this->analyzeSqlServerTable($db, $table));
                break;
        }

        return $structure;
    }

    /**
     * Analyze relationships between tables.
     */
    public function analyzeRelationships(string $connection, array $tables): array
    {
        $relationships = [];

        foreach ($tables as $table) {
            $foreignKeys = $this->introspector->getForeignKeys($connection, $table);
            
            foreach ($foreignKeys as $fk) {
                $relationships[] = [
                    'type' => 'foreign_key',
                    'from_table' => $table,
                    'from_column' => $fk['column'],
                    'to_table' => $fk['foreign_table'],
                    'to_column' => $fk['foreign_column'],
                    'constraint_name' => $fk['name'],
                    'on_update' => $fk['on_update'],
                    'on_delete' => $fk['on_delete'],
                ];
            }
        }

        return $relationships;
    }

    /**
     * Build dependency graph for table ordering.
     */
    public function buildDependencyGraph(array $tables): array
    {
        $dependencies = [];

        foreach ($tables as $tableName => $table) {
            $dependencies[$tableName] = [];
            
            foreach ($table['foreign_keys'] as $fk) {
                if ($fk['foreign_table'] !== $tableName) {
                    $dependencies[$tableName][] = $fk['foreign_table'];
                }
            }
        }

        return $dependencies;
    }

    /**
     * Order tables by foreign key dependencies.
     */
    public function orderTablesByDependencies(array $schema): array
    {
        $dependencies = $schema['dependencies'];
        $ordered = [];
        $visited = [];
        $visiting = [];

        $visit = function($table) use (&$visit, &$dependencies, &$ordered, &$visited, &$visiting) {
            if (isset($visited[$table])) {
                return;
            }

            if (isset($visiting[$table])) {
                // Circular dependency detected - add to ordered anyway
                return;
            }

            $visiting[$table] = true;

            foreach ($dependencies[$table] ?? [] as $dependency) {
                if (isset($dependencies[$dependency])) {
                    $visit($dependency);
                }
            }

            unset($visiting[$table]);
            $visited[$table] = true;
            $ordered[] = $table;
        };

        foreach (array_keys($dependencies) as $table) {
            $visit($table);
        }

        return $ordered;
    }

    /**
     * Get table constraints.
     */
    protected function getConstraints(string $connection, string $table): array
    {
        $db = DB::connection($connection);
        $driver = $db->getDriverName();

        switch ($driver) {
            case 'mysql':
            case 'mariadb':
                return $this->getMySqlConstraints($db, $table);
            case 'pgsql':
                return $this->getPostgresConstraints($db, $table);
            case 'sqlite':
                return $this->getSqliteConstraints($db, $table);
            case 'sqlsrv':
                return $this->getSqlServerConstraints($db, $table);
            default:
                return [];
        }
    }

    /**
     * Get table engine (MySQL/MariaDB specific).
     */
    protected function getTableEngine(string $connection, string $table): ?string
    {
        $db = DB::connection($connection);
        
        if (!in_array($db->getDriverName(), ['mysql', 'mariadb'])) {
            return null;
        }

        $result = $db->selectOne("
            SELECT ENGINE 
            FROM information_schema.TABLES 
            WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?
        ", [$db->getDatabaseName(), $table]);

        return $result->ENGINE ?? null;
    }

    /**
     * Get table charset.
     */
    protected function getTableCharset(string $connection, string $table): ?string
    {
        $db = DB::connection($connection);
        
        if (!in_array($db->getDriverName(), ['mysql', 'mariadb'])) {
            return null;
        }

        $result = $db->selectOne("
            SELECT TABLE_COLLATION
            FROM information_schema.TABLES 
            WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?
        ", [$db->getDatabaseName(), $table]);

        if ($result && $result->TABLE_COLLATION) {
            return explode('_', $result->TABLE_COLLATION)[0];
        }

        return null;
    }

    /**
     * Get table collation.
     */
    protected function getTableCollation(string $connection, string $table): ?string
    {
        $db = DB::connection($connection);
        
        if (!in_array($db->getDriverName(), ['mysql', 'mariadb'])) {
            return null;
        }

        $result = $db->selectOne("
            SELECT TABLE_COLLATION
            FROM information_schema.TABLES 
            WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?
        ", [$db->getDatabaseName(), $table]);

        return $result->TABLE_COLLATION ?? null;
    }

    /**
     * Get table comment.
     */
    protected function getTableComment(string $connection, string $table): ?string
    {
        $db = DB::connection($connection);
        $driver = $db->getDriverName();

        switch ($driver) {
            case 'mysql':
            case 'mariadb':
                $result = $db->selectOne("
                    SELECT TABLE_COMMENT
                    FROM information_schema.TABLES 
                    WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?
                ", [$db->getDatabaseName(), $table]);
                return $result->TABLE_COMMENT ?? null;

            case 'pgsql':
                $result = $db->selectOne("
                    SELECT obj_description(c.oid) as comment
                    FROM pg_class c
                    WHERE c.relname = ?
                ", [$table]);
                return $result->comment ?? null;

            default:
                return null;
        }
    }

    /**
     * Get table options.
     */
    protected function getTableOptions(string $connection, string $table): array
    {
        $options = [];
        $db = DB::connection($connection);
        $driver = $db->getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'])) {
            $result = $db->selectOne("
                SELECT 
                    AUTO_INCREMENT,
                    ROW_FORMAT,
                    TABLE_COMMENT
                FROM information_schema.TABLES 
                WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?
            ", [$db->getDatabaseName(), $table]);

            if ($result) {
                if ($result->AUTO_INCREMENT) {
                    $options['AUTO_INCREMENT'] = $result->AUTO_INCREMENT;
                }
                if ($result->ROW_FORMAT) {
                    $options['ROW_FORMAT'] = $result->ROW_FORMAT;
                }
            }
        }

        return $options;
    }

    /**
     * Database-specific analysis methods
     */

    protected function analyzeMySqlTable(Connection $db, string $table): array
    {
        return [
            'partitions' => $this->getMySqlPartitions($db, $table),
            'triggers' => $this->getMySqlTriggers($db, $table),
        ];
    }

    protected function analyzePostgresTable(Connection $db, string $table): array
    {
        return [
            'sequences' => $this->getPostgresSequences($db, $table),
            'triggers' => $this->getPostgresTriggers($db, $table),
        ];
    }

    protected function analyzeSqliteTable(Connection $db, string $table): array
    {
        return [
            'triggers' => $this->getSqliteTriggers($db, $table),
        ];
    }

    protected function analyzeSqlServerTable(Connection $db, string $table): array
    {
        return [
            'triggers' => $this->getSqlServerTriggers($db, $table),
        ];
    }

    // Constraint methods for different databases

    protected function getMySqlConstraints(Connection $db, string $table): array
    {
        $results = $db->select("
            SELECT 
                CONSTRAINT_NAME as name,
                CONSTRAINT_TYPE as type
            FROM information_schema.TABLE_CONSTRAINTS
            WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?
        ", [$db->getDatabaseName(), $table]);

        return array_map(function($constraint) {
            return [
                'name' => $constraint->name,
                'type' => $constraint->type,
            ];
        }, $results);
    }

    protected function getPostgresConstraints(Connection $db, string $table): array
    {
        $results = $db->select("
            SELECT 
                conname as name,
                contype as type
            FROM pg_constraint c
            JOIN pg_class t ON t.oid = c.conrelid
            WHERE t.relname = ?
        ", [$table]);

        $typeMap = [
            'p' => 'PRIMARY KEY',
            'f' => 'FOREIGN KEY',
            'u' => 'UNIQUE',
            'c' => 'CHECK',
        ];

        return array_map(function($constraint) use ($typeMap) {
            return [
                'name' => $constraint->name,
                'type' => $typeMap[$constraint->type] ?? $constraint->type,
            ];
        }, $results);
    }

    protected function getSqliteConstraints(Connection $db, string $table): array
    {
        // SQLite doesn't have a comprehensive constraints system like MySQL/Postgres
        return [];
    }

    protected function getSqlServerConstraints(Connection $db, string $table): array
    {
        $results = $db->select("
            SELECT 
                name,
                type_desc as type
            FROM sys.objects
            WHERE parent_object_id = OBJECT_ID(?)
            AND type IN ('PK', 'UQ', 'F', 'C')
        ", [$table]);

        return array_map(function($constraint) {
            return [
                'name' => $constraint->name,
                'type' => $constraint->type,
            ];
        }, $results);
    }

    // Additional database-specific methods

    protected function getMySqlPartitions(Connection $db, string $table): array
    {
        $results = $db->select("
            SELECT 
                PARTITION_NAME,
                PARTITION_METHOD,
                PARTITION_EXPRESSION
            FROM information_schema.PARTITIONS
            WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?
            AND PARTITION_NAME IS NOT NULL
        ", [$db->getDatabaseName(), $table]);

        return array_map(function($partition) {
            return [
                'name' => $partition->PARTITION_NAME,
                'method' => $partition->PARTITION_METHOD,
                'expression' => $partition->PARTITION_EXPRESSION,
            ];
        }, $results);
    }

    protected function getMySqlTriggers(Connection $db, string $table): array
    {
        $results = $db->select("
            SELECT TRIGGER_NAME, EVENT_MANIPULATION, ACTION_TIMING
            FROM information_schema.TRIGGERS
            WHERE EVENT_OBJECT_SCHEMA = ? AND EVENT_OBJECT_TABLE = ?
        ", [$db->getDatabaseName(), $table]);

        return array_map(function($trigger) {
            return [
                'name' => $trigger->TRIGGER_NAME,
                'event' => $trigger->EVENT_MANIPULATION,
                'timing' => $trigger->ACTION_TIMING,
            ];
        }, $results);
    }

    protected function getPostgresSequences(Connection $db, string $table): array
    {
        $results = $db->select("
            SELECT 
                s.relname as sequence_name,
                a.attname as column_name
            FROM pg_class s
            JOIN pg_depend d ON d.objid = s.oid
            JOIN pg_class t ON t.oid = d.refobjid
            JOIN pg_attribute a ON a.attrelid = d.refobjid AND a.attnum = d.refobjsubid
            WHERE s.relkind = 'S' AND t.relname = ?
        ", [$table]);

        return array_map(function($seq) {
            return [
                'name' => $seq->sequence_name,
                'column' => $seq->column_name,
            ];
        }, $results);
    }

    protected function getPostgresTriggers(Connection $db, string $table): array
    {
        $results = $db->select("
            SELECT 
                t.tgname as name,
                e.event as event,
                t.tgtype as timing
            FROM pg_trigger t
            JOIN pg_class c ON c.oid = t.tgrelid
            CROSS JOIN LATERAL unnest(string_to_array(
                CASE 
                    WHEN t.tgtype & 4 != 0 THEN 'INSERT'
                    ELSE ''
                END ||
                CASE 
                    WHEN t.tgtype & 8 != 0 THEN ',DELETE'
                    ELSE ''
                END ||
                CASE 
                    WHEN t.tgtype & 16 != 0 THEN ',UPDATE'
                    ELSE ''
                END, ','
            )) AS e(event)
            WHERE c.relname = ? AND NOT t.tgisinternal
        ", [$table]);

        return array_map(function($trigger) {
            return [
                'name' => $trigger->name,
                'event' => $trigger->event,
                'timing' => ($trigger->timing & 2) ? 'BEFORE' : 'AFTER',
            ];
        }, $results);
    }

    protected function getSqliteTriggers(Connection $db, string $table): array
    {
        $results = $db->select("
            SELECT name, sql
            FROM sqlite_master
            WHERE type = 'trigger' AND tbl_name = ?
        ", [$table]);

        return array_map(function($trigger) {
            return [
                'name' => $trigger->name,
                'sql' => $trigger->sql,
            ];
        }, $results);
    }

    protected function getSqlServerTriggers(Connection $db, string $table): array
    {
        $results = $db->select("
            SELECT 
                t.name,
                te.type_desc as event
            FROM sys.triggers t
            JOIN sys.trigger_events te ON t.object_id = te.object_id
            WHERE OBJECT_NAME(t.parent_id) = ?
        ", [$table]);

        return array_map(function($trigger) {
            return [
                'name' => $trigger->name,
                'event' => $trigger->event,
            ];
        }, $results);
    }
}