<?php

declare(strict_types=1);

namespace AutoGen\Tests\Helpers;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DatabaseTestHelper
{
    /**
     * Create a test table with various column types
     */
    public static function createTestTable(string $tableName, array $columns = []): void
    {
        Schema::create($tableName, function (Blueprint $table) use ($columns) {
            $table->id();
            
            if (empty($columns)) {
                // Default test columns
                $table->string('name');
                $table->string('email')->unique();
                $table->text('description')->nullable();
                $table->integer('age')->nullable();
                $table->boolean('is_active')->default(true);
                $table->decimal('price', 8, 2)->nullable();
                $table->json('metadata')->nullable();
                $table->timestamp('published_at')->nullable();
            } else {
                foreach ($columns as $column) {
                    self::addColumnToTable($table, $column);
                }
            }
            
            $table->timestamps();
        });
    }

    /**
     * Add column to table based on specification
     */
    private static function addColumnToTable(Blueprint $table, array $column): void
    {
        $name = $column['name'];
        $type = $column['type'];
        $attributes = $column['attributes'] ?? [];

        switch ($type) {
            case 'string':
                $col = $table->string($name, $attributes['length'] ?? 255);
                break;
            case 'text':
                $col = $table->text($name);
                break;
            case 'integer':
                $col = $table->integer($name);
                break;
            case 'bigInteger':
                $col = $table->bigInteger($name);
                break;
            case 'decimal':
                $col = $table->decimal($name, $attributes['precision'] ?? 8, $attributes['scale'] ?? 2);
                break;
            case 'float':
                $col = $table->float($name);
                break;
            case 'double':
                $col = $table->double($name);
                break;
            case 'boolean':
                $col = $table->boolean($name);
                break;
            case 'date':
                $col = $table->date($name);
                break;
            case 'time':
                $col = $table->time($name);
                break;
            case 'dateTime':
                $col = $table->dateTime($name);
                break;
            case 'timestamp':
                $col = $table->timestamp($name);
                break;
            case 'json':
                $col = $table->json($name);
                break;
            case 'enum':
                $col = $table->enum($name, $attributes['options'] ?? ['option1', 'option2']);
                break;
            case 'foreignId':
                $col = $table->foreignId($name);
                if (isset($attributes['references'])) {
                    $col->constrained($attributes['references'])->onDelete($attributes['onDelete'] ?? 'cascade');
                }
                break;
            default:
                $col = $table->string($name);
        }

        // Apply common attributes
        if ($attributes['nullable'] ?? false) {
            $col->nullable();
        }
        if (isset($attributes['default'])) {
            $col->default($attributes['default']);
        }
        if ($attributes['unique'] ?? false) {
            $col->unique();
        }
    }

    /**
     * Create table with relationships for testing
     */
    public static function createRelatedTables(): void
    {
        // Users table
        Schema::create('test_users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamps();
        });

        // Categories table
        Schema::create('test_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->timestamps();
        });

        // Posts table with relationships
        Schema::create('test_posts', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('content');
            $table->foreignId('user_id')->constrained('test_users')->onDelete('cascade');
            $table->foreignId('category_id')->constrained('test_categories')->onDelete('cascade');
            $table->timestamps();
        });

        // Many-to-many pivot table
        Schema::create('test_post_tags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained('test_posts')->onDelete('cascade');
            $table->foreignId('tag_id')->constrained('test_tags')->onDelete('cascade');
            $table->timestamps();
        });

        // Tags table
        Schema::create('test_tags', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });
    }

    /**
     * Drop test tables
     */
    public static function dropTestTables(array $tables): void
    {
        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                Schema::drop($table);
            }
        }
    }

    /**
     * Get table structure information
     */
    public static function getTableInfo(string $tableName): array
    {
        $columns = Schema::getColumnListing($tableName);
        $info = [];

        foreach ($columns as $column) {
            $columnType = Schema::getColumnType($tableName, $column);
            $info[$column] = [
                'type' => $columnType,
                'nullable' => true, // We'll assume nullable for testing
            ];
        }

        return $info;
    }

    /**
     * Insert test data into table
     */
    public static function insertTestData(string $tableName, array $data): void
    {
        \DB::table($tableName)->insert($data);
    }

    /**
     * Create indexes for testing
     */
    public static function createTestIndexes(string $tableName): void
    {
        Schema::table($tableName, function (Blueprint $table) {
            $table->index(['name'], 'test_name_index');
            $table->index(['created_at', 'updated_at'], 'test_timestamps_index');
        });
    }
}