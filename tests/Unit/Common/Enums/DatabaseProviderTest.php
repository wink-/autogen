<?php

declare(strict_types=1);

namespace Tests\Unit\Common\Enums;

use AutoGen\Common\Enums\DatabaseProvider;
use Tests\TestCase;

class DatabaseProviderTest extends TestCase
{
    public function test_enum_values(): void
    {
        $this->assertEquals('mysql', DatabaseProvider::MySQL->value);
        $this->assertEquals('postgresql', DatabaseProvider::PostgreSQL->value);
        $this->assertEquals('sqlite', DatabaseProvider::SQLite->value);
    }

    public function test_labels(): void
    {
        $this->assertEquals('MySQL', DatabaseProvider::MySQL->label());
        $this->assertEquals('PostgreSQL', DatabaseProvider::PostgreSQL->label());
        $this->assertEquals('SQLite', DatabaseProvider::SQLite->label());
    }

    public function test_drivers(): void
    {
        $this->assertEquals('mysql', DatabaseProvider::MySQL->driver());
        $this->assertEquals('pgsql', DatabaseProvider::PostgreSQL->driver());
        $this->assertEquals('sqlite', DatabaseProvider::SQLite->driver());
    }

    public function test_default_ports(): void
    {
        $this->assertEquals(3306, DatabaseProvider::MySQL->defaultPort());
        $this->assertEquals(5432, DatabaseProvider::PostgreSQL->defaultPort());
        $this->assertEquals(0, DatabaseProvider::SQLite->defaultPort());
    }

    public function test_json_support(): void
    {
        $this->assertTrue(DatabaseProvider::MySQL->supportsJson());
        $this->assertTrue(DatabaseProvider::PostgreSQL->supportsJson());
        $this->assertTrue(DatabaseProvider::SQLite->supportsJson());
    }

    public function test_config_paths(): void
    {
        $this->assertEquals('config/database-providers/mysql.php', DatabaseProvider::MySQL->configPath());
        $this->assertEquals('config/database-providers/postgresql.php', DatabaseProvider::PostgreSQL->configPath());
        $this->assertEquals('config/database-providers/sqlite.php', DatabaseProvider::SQLite->configPath());
    }

    public function test_values_method(): void
    {
        $expected = ['mysql', 'postgresql', 'sqlite'];
        $this->assertEquals($expected, DatabaseProvider::values());
    }

    public function test_options_method(): void
    {
        $expected = [
            'MySQL' => 'mysql',
            'PostgreSQL' => 'postgresql',
            'SQLite' => 'sqlite',
        ];
        $this->assertEquals($expected, DatabaseProvider::options());
    }

    public function test_from_string(): void
    {
        $this->assertEquals(DatabaseProvider::MySQL, DatabaseProvider::fromString('mysql'));
        $this->assertEquals(DatabaseProvider::MySQL, DatabaseProvider::fromString('MySQL'));
        $this->assertEquals(DatabaseProvider::PostgreSQL, DatabaseProvider::fromString('postgresql'));
        $this->assertEquals(DatabaseProvider::SQLite, DatabaseProvider::fromString('sqlite'));
    }

    public function test_try_from_string(): void
    {
        $this->assertEquals(DatabaseProvider::MySQL, DatabaseProvider::tryFromString('mysql'));
        $this->assertNull(DatabaseProvider::tryFromString('invalid'));
        $this->assertNull(DatabaseProvider::tryFromString(null));
    }
}