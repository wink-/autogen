<?php

declare(strict_types=1);

namespace Tests\Unit\Common\Traits;

use AutoGen\Common\Enums\CssFramework;
use AutoGen\Common\Enums\DatabaseProvider;
use AutoGen\Common\Enums\RelationshipType;
use AutoGen\Common\Traits\EnumBackwardCompatibility;
use Tests\TestCase;

class EnumBackwardCompatibilityTest extends TestCase
{
    use EnumBackwardCompatibility;

    public function test_to_css_framework_with_string(): void
    {
        $this->assertEquals(CssFramework::Tailwind, $this->toCssFramework('tailwind'));
        $this->assertEquals(CssFramework::Tailwind, $this->toCssFramework('tw'));
        $this->assertEquals(CssFramework::Bootstrap, $this->toCssFramework('bootstrap'));
        $this->assertEquals(CssFramework::Bootstrap, $this->toCssFramework('bs'));
        $this->assertEquals(CssFramework::PlainCSS, $this->toCssFramework('css'));
        $this->assertEquals(CssFramework::PlainCSS, $this->toCssFramework('plain'));
    }

    public function test_to_css_framework_with_enum(): void
    {
        $this->assertEquals(CssFramework::Tailwind, $this->toCssFramework(CssFramework::Tailwind));
        $this->assertEquals(CssFramework::Bootstrap, $this->toCssFramework(CssFramework::Bootstrap));
        $this->assertEquals(CssFramework::PlainCSS, $this->toCssFramework(CssFramework::PlainCSS));
    }

    public function test_to_css_framework_with_invalid_string(): void
    {
        // Should default to Tailwind for invalid values
        $this->assertEquals(CssFramework::Tailwind, $this->toCssFramework('invalid'));
        $this->assertEquals(CssFramework::Tailwind, $this->toCssFramework('unknown'));
    }

    public function test_to_database_provider_with_string(): void
    {
        $this->assertEquals(DatabaseProvider::MySQL, $this->toDatabaseProvider('mysql'));
        $this->assertEquals(DatabaseProvider::MySQL, $this->toDatabaseProvider('mariadb'));
        $this->assertEquals(DatabaseProvider::PostgreSQL, $this->toDatabaseProvider('postgresql'));
        $this->assertEquals(DatabaseProvider::PostgreSQL, $this->toDatabaseProvider('pgsql'));
        $this->assertEquals(DatabaseProvider::PostgreSQL, $this->toDatabaseProvider('postgres'));
        $this->assertEquals(DatabaseProvider::SQLite, $this->toDatabaseProvider('sqlite'));
        $this->assertEquals(DatabaseProvider::SQLite, $this->toDatabaseProvider('sqlite3'));
    }

    public function test_to_database_provider_with_enum(): void
    {
        $this->assertEquals(DatabaseProvider::MySQL, $this->toDatabaseProvider(DatabaseProvider::MySQL));
        $this->assertEquals(DatabaseProvider::PostgreSQL, $this->toDatabaseProvider(DatabaseProvider::PostgreSQL));
        $this->assertEquals(DatabaseProvider::SQLite, $this->toDatabaseProvider(DatabaseProvider::SQLite));
    }

    public function test_to_relationship_type_with_string(): void
    {
        $this->assertEquals(RelationshipType::BelongsTo, $this->toRelationshipType('belongsTo'));
        $this->assertEquals(RelationshipType::BelongsTo, $this->toRelationshipType('belongs_to'));
        $this->assertEquals(RelationshipType::BelongsTo, $this->toRelationshipType('belongs-to'));
        $this->assertEquals(RelationshipType::HasMany, $this->toRelationshipType('hasMany'));
        $this->assertEquals(RelationshipType::HasMany, $this->toRelationshipType('has_many'));
        $this->assertEquals(RelationshipType::HasMany, $this->toRelationshipType('has-many'));
    }

    public function test_to_relationship_type_with_enum(): void
    {
        $this->assertEquals(RelationshipType::BelongsTo, $this->toRelationshipType(RelationshipType::BelongsTo));
        $this->assertEquals(RelationshipType::HasMany, $this->toRelationshipType(RelationshipType::HasMany));
    }

    public function test_enum_to_string(): void
    {
        $this->assertEquals('tailwind', $this->enumToString(CssFramework::Tailwind));
        $this->assertEquals('mysql', $this->enumToString(DatabaseProvider::MySQL));
        $this->assertEquals('belongsTo', $this->enumToString(RelationshipType::BelongsTo));
    }

    public function test_enums_to_strings(): void
    {
        $enums = [
            CssFramework::Tailwind,
            CssFramework::Bootstrap,
            'already_string',
        ];

        $expected = ['tailwind', 'bootstrap', 'already_string'];
        $this->assertEquals($expected, $this->enumsToStrings($enums));
    }

    public function test_is_valid_enum_value(): void
    {
        $this->assertTrue($this->isValidEnumValue(CssFramework::class, 'tailwind'));
        $this->assertTrue($this->isValidEnumValue(CssFramework::class, CssFramework::Tailwind));
        $this->assertFalse($this->isValidEnumValue(CssFramework::class, 'invalid'));
        $this->assertFalse($this->isValidEnumValue(CssFramework::class, 123));
    }

    public function test_safe_to_enum(): void
    {
        $this->assertEquals(CssFramework::Tailwind, $this->safeToEnum(CssFramework::class, 'tailwind'));
        $this->assertEquals(CssFramework::Tailwind, $this->safeToEnum(CssFramework::class, CssFramework::Tailwind));
        $this->assertNull($this->safeToEnum(CssFramework::class, 'invalid'));
        $this->assertNull($this->safeToEnum(CssFramework::class, 123));
    }

    public function test_get_all_valid_values(): void
    {
        $cssValues = $this->getAllValidValues(CssFramework::class);
        $this->assertContains('tailwind', $cssValues);
        $this->assertContains('bootstrap', $cssValues);
        $this->assertContains('css', $cssValues);
        $this->assertContains('tw', $cssValues);
        $this->assertContains('bs', $cssValues);

        $dbValues = $this->getAllValidValues(DatabaseProvider::class);
        $this->assertContains('mysql', $dbValues);
        $this->assertContains('postgresql', $dbValues);
        $this->assertContains('sqlite', $dbValues);
        $this->assertContains('mariadb', $dbValues);
        $this->assertContains('pgsql', $dbValues);
    }
}