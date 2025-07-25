<?php

declare(strict_types=1);

namespace AutoGen\Packages\Model;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Connection;

class DatabaseIntrospector
{
    /**
     * Get all tables from the database connection.
     */
    public function getTables(string $connection): array
    {
        $tables = [];
        $db = DB::connection($connection);
        
        switch ($db->getDriverName()) {
            case 'mysql':
            case 'mariadb':
                $tables = $this->getMySqlTables($db);
                break;
            case 'pgsql':
                $tables = $this->getPostgresTables($db);
                break;
            case 'sqlite':
                $tables = $this->getSqliteTables($db);
                break;
            case 'sqlsrv':
                $tables = $this->getSqlServerTables($db);
                break;
        }
        
        return $tables;
    }

    /**
     * Introspect a table's structure.
     */
    public function introspectTable(string $connection, string $table): array
    {
        $db = DB::connection($connection);
        $schema = Schema::connection($connection);
        
        $structure = [
            'name' => $table,
            'columns' => $this->getColumns($connection, $table),
            'indexes' => $this->getIndexes($connection, $table),
            'primary_key' => $this->getPrimaryKey($connection, $table),
            'foreign_keys' => $this->getForeignKeys($connection, $table),
            'has_timestamps' => $this->hasTimestamps($connection, $table),
            'has_soft_deletes' => $this->hasSoftDeletes($connection, $table),
        ];
        
        return $structure;
    }

    /**
     * Get columns information for a table.
     */
    public function getColumns(string $connection, string $table): array
    {
        $columns = [];
        $db = DB::connection($connection);
        
        switch ($db->getDriverName()) {
            case 'mysql':
            case 'mariadb':
                $columns = $this->getMySqlColumns($db, $table);
                break;
            case 'pgsql':
                $columns = $this->getPostgresColumns($db, $table);
                break;
            case 'sqlite':
                $columns = $this->getSqliteColumns($db, $table);
                break;
            case 'sqlsrv':
                $columns = $this->getSqlServerColumns($db, $table);
                break;
        }
        
        return $columns;
    }

    /**
     * Get indexes for a table.
     */
    public function getIndexes(string $connection, string $table): array
    {
        $indexes = [];
        $db = DB::connection($connection);
        
        switch ($db->getDriverName()) {
            case 'mysql':
            case 'mariadb':
                $indexes = $this->getMySqlIndexes($db, $table);
                break;
            case 'pgsql':
                $indexes = $this->getPostgresIndexes($db, $table);
                break;
            case 'sqlite':
                $indexes = $this->getSqliteIndexes($db, $table);
                break;
            case 'sqlsrv':
                $indexes = $this->getSqlServerIndexes($db, $table);
                break;
        }
        
        return $indexes;
    }

    /**
     * Get primary key information.
     */
    public function getPrimaryKey(string $connection, string $table): ?array
    {
        $db = DB::connection($connection);
        
        switch ($db->getDriverName()) {
            case 'mysql':
            case 'mariadb':
                return $this->getMySqlPrimaryKey($db, $table);
            case 'pgsql':
                return $this->getPostgresPrimaryKey($db, $table);
            case 'sqlite':
                return $this->getSqlitePrimaryKey($db, $table);
            case 'sqlsrv':
                return $this->getSqlServerPrimaryKey($db, $table);
        }
        
        return null;
    }

    /**
     * Get foreign key constraints.
     */
    public function getForeignKeys(string $connection, string $table): array
    {
        $foreignKeys = [];
        $db = DB::connection($connection);
        
        switch ($db->getDriverName()) {
            case 'mysql':
            case 'mariadb':
                $foreignKeys = $this->getMySqlForeignKeys($db, $table);
                break;
            case 'pgsql':
                $foreignKeys = $this->getPostgresForeignKeys($db, $table);
                break;
            case 'sqlite':
                $foreignKeys = $this->getSqliteForeignKeys($db, $table);
                break;
            case 'sqlsrv':
                $foreignKeys = $this->getSqlServerForeignKeys($db, $table);
                break;
        }
        
        return $foreignKeys;
    }

    /**
     * Check if table has timestamp columns.
     */
    public function hasTimestamps(string $connection, string $table): bool
    {
        $columns = $this->getColumns($connection, $table);
        $columnNames = array_column($columns, 'name');
        
        return in_array('created_at', $columnNames) && in_array('updated_at', $columnNames);
    }

    /**
     * Check if table has soft delete column.
     */
    public function hasSoftDeletes(string $connection, string $table): bool
    {
        $columns = $this->getColumns($connection, $table);
        $columnNames = array_column($columns, 'name');
        
        return in_array('deleted_at', $columnNames);
    }

    // MySQL/MariaDB specific methods
    
    protected function getMySqlTables(Connection $db): array
    {
        $database = $db->getDatabaseName();
        $results = $db->select("
            SELECT TABLE_NAME 
            FROM information_schema.TABLES 
            WHERE TABLE_SCHEMA = ? 
            AND TABLE_TYPE = 'BASE TABLE'
        ", [$database]);
        
        return array_column($results, 'TABLE_NAME');
    }

    protected function getMySqlColumns(Connection $db, string $table): array
    {
        $database = $db->getDatabaseName();
        $results = $db->select("
            SELECT 
                COLUMN_NAME as name,
                DATA_TYPE as type,
                COLUMN_TYPE as full_type,
                IS_NULLABLE as nullable,
                COLUMN_DEFAULT as default_value,
                CHARACTER_MAXIMUM_LENGTH as length,
                NUMERIC_PRECISION as precision,
                NUMERIC_SCALE as scale,
                COLUMN_COMMENT as comment,
                EXTRA as extra
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = ?
            AND TABLE_NAME = ?
            ORDER BY ORDINAL_POSITION
        ", [$database, $table]);
        
        return array_map(function ($column) {
            return [
                'name' => $column->name,
                'type' => $column->type,
                'full_type' => $column->full_type,
                'nullable' => $column->nullable === 'YES',
                'default' => $column->default_value,
                'length' => $column->length,
                'precision' => $column->precision,
                'scale' => $column->scale,
                'comment' => $column->comment,
                'auto_increment' => str_contains($column->extra, 'auto_increment'),
                'unsigned' => str_contains($column->full_type, 'unsigned'),
            ];
        }, $results);
    }

    protected function getMySqlIndexes(Connection $db, string $table): array
    {
        $database = $db->getDatabaseName();
        $results = $db->select("
            SELECT 
                INDEX_NAME as name,
                GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX) as columns,
                NON_UNIQUE as non_unique,
                INDEX_TYPE as type
            FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = ?
            AND TABLE_NAME = ?
            AND INDEX_NAME != 'PRIMARY'
            GROUP BY INDEX_NAME, NON_UNIQUE, INDEX_TYPE
        ", [$database, $table]);
        
        return array_map(function ($index) {
            return [
                'name' => $index->name,
                'columns' => explode(',', $index->columns),
                'unique' => !$index->non_unique,
                'type' => $index->type,
            ];
        }, $results);
    }

    protected function getMySqlPrimaryKey(Connection $db, string $table): ?array
    {
        $database = $db->getDatabaseName();
        $results = $db->select("
            SELECT COLUMN_NAME as name
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = ?
            AND TABLE_NAME = ?
            AND CONSTRAINT_NAME = 'PRIMARY'
            ORDER BY ORDINAL_POSITION
        ", [$database, $table]);
        
        if (empty($results)) {
            return null;
        }
        
        $columns = array_column($results, 'name');
        
        return [
            'columns' => $columns,
            'composite' => count($columns) > 1,
        ];
    }

    protected function getMySqlForeignKeys(Connection $db, string $table): array
    {
        $database = $db->getDatabaseName();
        $results = $db->select("
            SELECT 
                kcu.CONSTRAINT_NAME as name,
                kcu.COLUMN_NAME as column_name,
                kcu.REFERENCED_TABLE_NAME as foreign_table,
                kcu.REFERENCED_COLUMN_NAME as foreign_column,
                rc.UPDATE_RULE as on_update,
                rc.DELETE_RULE as on_delete
            FROM information_schema.KEY_COLUMN_USAGE kcu
            JOIN information_schema.REFERENTIAL_CONSTRAINTS rc
                ON kcu.CONSTRAINT_NAME = rc.CONSTRAINT_NAME
                AND kcu.TABLE_SCHEMA = rc.CONSTRAINT_SCHEMA
            WHERE kcu.TABLE_SCHEMA = ?
            AND kcu.TABLE_NAME = ?
            AND kcu.REFERENCED_TABLE_NAME IS NOT NULL
        ", [$database, $table]);
        
        return array_map(function ($fk) {
            return [
                'name' => $fk->name,
                'column' => $fk->column_name,
                'foreign_table' => $fk->foreign_table,
                'foreign_column' => $fk->foreign_column,
                'on_update' => $fk->on_update,
                'on_delete' => $fk->on_delete,
            ];
        }, $results);
    }

    // PostgreSQL specific methods
    
    protected function getPostgresTables(Connection $db): array
    {
        $results = $db->select("
            SELECT tablename as table_name
            FROM pg_tables
            WHERE schemaname = 'public'
        ");
        
        return array_column($results, 'table_name');
    }

    protected function getPostgresColumns(Connection $db, string $table): array
    {
        $results = $db->select("
            SELECT 
                column_name as name,
                data_type as type,
                is_nullable as nullable,
                column_default as default_value,
                character_maximum_length as length,
                numeric_precision as precision,
                numeric_scale as scale
            FROM information_schema.columns
            WHERE table_schema = 'public'
            AND table_name = ?
            ORDER BY ordinal_position
        ", [$table]);
        
        return array_map(function ($column) {
            return [
                'name' => $column->name,
                'type' => $column->type,
                'nullable' => $column->nullable === 'YES',
                'default' => $column->default_value,
                'length' => $column->length,
                'precision' => $column->precision,
                'scale' => $column->scale,
                'auto_increment' => str_contains($column->default_value ?? '', 'nextval'),
            ];
        }, $results);
    }

    protected function getPostgresIndexes(Connection $db, string $table): array
    {
        $results = $db->select("
            SELECT 
                i.relname as name,
                array_agg(a.attname ORDER BY x.n) as columns,
                indisunique as is_unique,
                am.amname as type
            FROM pg_index ix
            JOIN pg_class t ON t.oid = ix.indrelid
            JOIN pg_class i ON i.oid = ix.indexrelid
            JOIN pg_am am ON am.oid = i.relam
            CROSS JOIN LATERAL unnest(ix.indkey) WITH ORDINALITY AS x(attnum, n)
            JOIN pg_attribute a ON a.attnum = x.attnum AND a.attrelid = t.oid
            WHERE t.relname = ?
            AND NOT ix.indisprimary
            GROUP BY i.relname, ix.indisunique, am.amname
        ", [$table]);
        
        return array_map(function ($index) {
            return [
                'name' => $index->name,
                'columns' => $index->columns,
                'unique' => $index->is_unique,
                'type' => strtoupper($index->type),
            ];
        }, $results);
    }

    protected function getPostgresPrimaryKey(Connection $db, string $table): ?array
    {
        $results = $db->select("
            SELECT a.attname as name
            FROM pg_index i
            JOIN pg_class c ON c.oid = i.indrelid
            CROSS JOIN LATERAL unnest(i.indkey) WITH ORDINALITY AS k(attnum, n)
            JOIN pg_attribute a ON a.attnum = k.attnum AND a.attrelid = c.oid
            WHERE c.relname = ?
            AND i.indisprimary
            ORDER BY k.n
        ", [$table]);
        
        if (empty($results)) {
            return null;
        }
        
        $columns = array_column($results, 'name');
        
        return [
            'columns' => $columns,
            'composite' => count($columns) > 1,
        ];
    }

    protected function getPostgresForeignKeys(Connection $db, string $table): array
    {
        $results = $db->select("
            SELECT 
                tc.constraint_name as name,
                kcu.column_name as column_name,
                ccu.table_name as foreign_table,
                ccu.column_name as foreign_column,
                rc.update_rule as on_update,
                rc.delete_rule as on_delete
            FROM information_schema.table_constraints tc
            JOIN information_schema.key_column_usage kcu
                ON tc.constraint_name = kcu.constraint_name
                AND tc.table_schema = kcu.table_schema
            JOIN information_schema.constraint_column_usage ccu
                ON ccu.constraint_name = tc.constraint_name
                AND ccu.table_schema = tc.table_schema
            JOIN information_schema.referential_constraints rc
                ON rc.constraint_name = tc.constraint_name
                AND rc.constraint_schema = tc.table_schema
            WHERE tc.constraint_type = 'FOREIGN KEY'
            AND tc.table_name = ?
        ", [$table]);
        
        return array_map(function ($fk) {
            return [
                'name' => $fk->name,
                'column' => $fk->column_name,
                'foreign_table' => $fk->foreign_table,
                'foreign_column' => $fk->foreign_column,
                'on_update' => $fk->on_update,
                'on_delete' => $fk->on_delete,
            ];
        }, $results);
    }

    // SQLite specific methods
    
    protected function getSqliteTables(Connection $db): array
    {
        $results = $db->select("
            SELECT name as table_name
            FROM sqlite_master
            WHERE type = 'table'
            AND name NOT LIKE 'sqlite_%'
        ");
        
        return array_column($results, 'table_name');
    }

    protected function getSqliteColumns(Connection $db, string $table): array
    {
        $results = $db->select("PRAGMA table_info({$table})");
        
        return array_map(function ($column) {
            return [
                'name' => $column->name,
                'type' => strtolower($column->type),
                'nullable' => !$column->notnull,
                'default' => $column->dflt_value,
                'primary_key' => (bool) $column->pk,
            ];
        }, $results);
    }

    protected function getSqliteIndexes(Connection $db, string $table): array
    {
        $indexes = [];
        $indexList = $db->select("PRAGMA index_list({$table})");
        
        foreach ($indexList as $index) {
            if ($index->origin === 'pk') {
                continue; // Skip primary key index
            }
            
            $columns = $db->select("PRAGMA index_info({$index->name})");
            
            $indexes[] = [
                'name' => $index->name,
                'columns' => array_column($columns, 'name'),
                'unique' => (bool) $index->unique,
                'type' => 'BTREE',
            ];
        }
        
        return $indexes;
    }

    protected function getSqlitePrimaryKey(Connection $db, string $table): ?array
    {
        $columns = $db->select("PRAGMA table_info({$table})");
        $primaryColumns = array_filter($columns, fn($col) => $col->pk > 0);
        
        if (empty($primaryColumns)) {
            return null;
        }
        
        usort($primaryColumns, fn($a, $b) => $a->pk <=> $b->pk);
        $columnNames = array_column($primaryColumns, 'name');
        
        return [
            'columns' => $columnNames,
            'composite' => count($columnNames) > 1,
        ];
    }

    protected function getSqliteForeignKeys(Connection $db, string $table): array
    {
        $results = $db->select("PRAGMA foreign_key_list({$table})");
        
        return array_map(function ($fk) {
            return [
                'name' => "fk_{$fk->id}",
                'column' => $fk->from,
                'foreign_table' => $fk->table,
                'foreign_column' => $fk->to,
                'on_update' => $fk->on_update,
                'on_delete' => $fk->on_delete,
            ];
        }, $results);
    }

    // SQL Server specific methods
    
    protected function getSqlServerTables(Connection $db): array
    {
        $results = $db->select("
            SELECT TABLE_NAME
            FROM INFORMATION_SCHEMA.TABLES
            WHERE TABLE_TYPE = 'BASE TABLE'
            AND TABLE_SCHEMA = 'dbo'
        ");
        
        return array_column($results, 'TABLE_NAME');
    }

    protected function getSqlServerColumns(Connection $db, string $table): array
    {
        $results = $db->select("
            SELECT 
                c.COLUMN_NAME as name,
                c.DATA_TYPE as type,
                c.IS_NULLABLE as nullable,
                c.COLUMN_DEFAULT as default_value,
                c.CHARACTER_MAXIMUM_LENGTH as length,
                c.NUMERIC_PRECISION as precision,
                c.NUMERIC_SCALE as scale,
                COLUMNPROPERTY(OBJECT_ID(c.TABLE_SCHEMA + '.' + c.TABLE_NAME), c.COLUMN_NAME, 'IsIdentity') as is_identity
            FROM INFORMATION_SCHEMA.COLUMNS c
            WHERE c.TABLE_NAME = ?
            AND c.TABLE_SCHEMA = 'dbo'
            ORDER BY c.ORDINAL_POSITION
        ", [$table]);
        
        return array_map(function ($column) {
            return [
                'name' => $column->name,
                'type' => $column->type,
                'nullable' => $column->nullable === 'YES',
                'default' => $column->default_value,
                'length' => $column->length,
                'precision' => $column->precision,
                'scale' => $column->scale,
                'auto_increment' => (bool) $column->is_identity,
            ];
        }, $results);
    }

    protected function getSqlServerIndexes(Connection $db, string $table): array
    {
        $results = $db->select("
            SELECT 
                i.name,
                STRING_AGG(c.name, ',') WITHIN GROUP (ORDER BY ic.key_ordinal) as columns,
                i.is_unique,
                i.type_desc
            FROM sys.indexes i
            JOIN sys.index_columns ic ON i.object_id = ic.object_id AND i.index_id = ic.index_id
            JOIN sys.columns c ON ic.object_id = c.object_id AND ic.column_id = c.column_id
            WHERE OBJECT_NAME(i.object_id) = ?
            AND i.is_primary_key = 0
            GROUP BY i.name, i.is_unique, i.type_desc
        ", [$table]);
        
        return array_map(function ($index) {
            return [
                'name' => $index->name,
                'columns' => explode(',', $index->columns),
                'unique' => (bool) $index->is_unique,
                'type' => $index->type_desc,
            ];
        }, $results);
    }

    protected function getSqlServerPrimaryKey(Connection $db, string $table): ?array
    {
        $results = $db->select("
            SELECT c.name
            FROM sys.indexes i
            JOIN sys.index_columns ic ON i.object_id = ic.object_id AND i.index_id = ic.index_id
            JOIN sys.columns c ON ic.object_id = c.object_id AND ic.column_id = c.column_id
            WHERE OBJECT_NAME(i.object_id) = ?
            AND i.is_primary_key = 1
            ORDER BY ic.key_ordinal
        ", [$table]);
        
        if (empty($results)) {
            return null;
        }
        
        $columns = array_column($results, 'name');
        
        return [
            'columns' => $columns,
            'composite' => count($columns) > 1,
        ];
    }

    protected function getSqlServerForeignKeys(Connection $db, string $table): array
    {
        $results = $db->select("
            SELECT 
                fk.name,
                COL_NAME(fkc.parent_object_id, fkc.parent_column_id) as column_name,
                OBJECT_NAME(fkc.referenced_object_id) as foreign_table,
                COL_NAME(fkc.referenced_object_id, fkc.referenced_column_id) as foreign_column,
                fk.update_referential_action_desc as on_update,
                fk.delete_referential_action_desc as on_delete
            FROM sys.foreign_keys fk
            JOIN sys.foreign_key_columns fkc ON fk.object_id = fkc.constraint_object_id
            WHERE OBJECT_NAME(fk.parent_object_id) = ?
        ", [$table]);
        
        return array_map(function ($fk) {
            return [
                'name' => $fk->name,
                'column' => $fk->column_name,
                'foreign_table' => $fk->foreign_table,
                'foreign_column' => $fk->foreign_column,
                'on_update' => $fk->on_update,
                'on_delete' => $fk->on_delete,
            ];
        }, $results);
    }
}