<?php

declare(strict_types=1);

namespace Tests\Unit\Common\Enums;

use AutoGen\Common\Enums\RelationshipType;
use Tests\TestCase;

class RelationshipTypeTest extends TestCase
{
    public function test_enum_values(): void
    {
        $this->assertEquals('belongsTo', RelationshipType::BelongsTo->value);
        $this->assertEquals('hasMany', RelationshipType::HasMany->value);
        $this->assertEquals('belongsToMany', RelationshipType::BelongsToMany->value);
        $this->assertEquals('morphTo', RelationshipType::MorphTo->value);
    }

    public function test_labels(): void
    {
        $this->assertEquals('Belongs To', RelationshipType::BelongsTo->label());
        $this->assertEquals('Has Many', RelationshipType::HasMany->label());
        $this->assertEquals('Belongs To Many', RelationshipType::BelongsToMany->label());
        $this->assertEquals('Morph To', RelationshipType::MorphTo->label());
    }

    public function test_cardinality(): void
    {
        $this->assertEquals('one', RelationshipType::BelongsTo->cardinality());
        $this->assertEquals('one', RelationshipType::HasOne->cardinality());
        $this->assertEquals('many', RelationshipType::HasMany->cardinality());
        $this->assertEquals('many', RelationshipType::BelongsToMany->cardinality());
    }

    public function test_is_polymorphic(): void
    {
        $this->assertFalse(RelationshipType::BelongsTo->isPolymorphic());
        $this->assertFalse(RelationshipType::HasMany->isPolymorphic());
        $this->assertTrue(RelationshipType::MorphTo->isPolymorphic());
        $this->assertTrue(RelationshipType::MorphMany->isPolymorphic());
    }

    public function test_uses_pivot(): void
    {
        $this->assertFalse(RelationshipType::BelongsTo->usesPivot());
        $this->assertFalse(RelationshipType::HasMany->usesPivot());
        $this->assertTrue(RelationshipType::BelongsToMany->usesPivot());
        $this->assertTrue(RelationshipType::MorphToMany->usesPivot());
    }

    public function test_is_reverse(): void
    {
        $this->assertTrue(RelationshipType::BelongsTo->isReverse());
        $this->assertFalse(RelationshipType::HasMany->isReverse());
        $this->assertTrue(RelationshipType::MorphTo->isReverse());
        $this->assertFalse(RelationshipType::MorphMany->isReverse());
    }

    public function test_method_parameters(): void
    {
        $belongsToParams = RelationshipType::BelongsTo->methodParameters();
        $this->assertIsArray($belongsToParams);
        $this->assertContains('$related', $belongsToParams);
        $this->assertContains('$foreignKey = null', $belongsToParams);

        $hasManyParams = RelationshipType::HasMany->methodParameters();
        $this->assertIsArray($hasManyParams);
        $this->assertContains('$related', $hasManyParams);
    }

    public function test_inverse(): void
    {
        $this->assertEquals(RelationshipType::HasOne, RelationshipType::BelongsTo->inverse());
        $this->assertEquals(RelationshipType::BelongsTo, RelationshipType::HasMany->inverse());
        $this->assertEquals(RelationshipType::BelongsToMany, RelationshipType::BelongsToMany->inverse());
        $this->assertNull(RelationshipType::HasOneThrough->inverse());
    }

    public function test_values_method(): void
    {
        $values = RelationshipType::values();
        $this->assertIsArray($values);
        $this->assertContains('belongsTo', $values);
        $this->assertContains('hasMany', $values);
        $this->assertContains('morphTo', $values);
    }

    public function test_polymorphic_method(): void
    {
        $polymorphic = RelationshipType::polymorphic();
        $this->assertContains(RelationshipType::MorphTo, $polymorphic);
        $this->assertContains(RelationshipType::MorphMany, $polymorphic);
        $this->assertNotContains(RelationshipType::BelongsTo, $polymorphic);
    }

    public function test_with_pivot_method(): void
    {
        $withPivot = RelationshipType::withPivot();
        $this->assertContains(RelationshipType::BelongsToMany, $withPivot);
        $this->assertContains(RelationshipType::MorphToMany, $withPivot);
        $this->assertNotContains(RelationshipType::HasMany, $withPivot);
    }

    public function test_simple_method(): void
    {
        $simple = RelationshipType::simple();
        $this->assertContains(RelationshipType::BelongsTo, $simple);
        $this->assertContains(RelationshipType::HasMany, $simple);
        $this->assertNotContains(RelationshipType::MorphTo, $simple);
        $this->assertNotContains(RelationshipType::HasOneThrough, $simple);
    }
}