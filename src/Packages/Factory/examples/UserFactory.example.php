<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = User::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => $this->faker->phoneNumber(),
            'address' => $this->faker->address(),
            'bio' => $this->faker->paragraph(2),
            'age' => $this->faker->numberBetween(18, 80),
            'is_active' => $this->faker->boolean(80),
            'is_verified' => $this->faker->boolean(60),
            'avatar' => $this->faker->imageUrl(200, 200),
            'created_at' => $this->faker->dateTimeBetween('-2 years', 'now'),
        ];
    }

    /**
     * Indicate that the user is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * Indicate that the user is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the user is verified.
     */
    public function verified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => now(),
            'is_verified' => true,
        ]);
    }

    /**
     * Indicate that the user is unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
            'is_verified' => false,
        ]);
    }

    /**
     * Create with posts relationship.
     */
    public function withPosts($count = 3, $attributes = []): static
    {
        return $this->afterCreating(function ($model) use ($count, $attributes) {
            Post::factory()->count($count)->create(array_merge([
                $model->getForeignKey() => $model->id,
            ], $attributes));
        });
    }

    /**
     * Create with profile relationship.
     */
    public function withProfile($attributes = []): static
    {
        return $this->afterCreating(function ($model) use ($attributes) {
            Profile::factory()->create(array_merge([
                $model->getForeignKey() => $model->id,
            ], $attributes));
        });
    }

    /**
     * Create with roles relationship (BelongsToMany).
     */
    public function withRoles($count = 2, $attributes = []): static
    {
        return $this->afterCreating(function ($model) use ($count, $attributes) {
            $related = Role::factory()->count($count)->create($attributes);
            $model->roles()->attach($related);
        });
    }

    /**
     * Create with all relationships populated.
     */
    public function withRelationships(): static
    {
        return $this
            ->withPosts(3)
            ->withProfile()
            ->withRoles(2);
    }
}