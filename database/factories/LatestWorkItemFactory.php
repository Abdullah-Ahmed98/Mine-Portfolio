<?php

namespace Database\Factories;

use App\Models\LatestWorkItem;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<LatestWorkItem>
 */
class LatestWorkItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->unique()->words(3, true);

        return [
            'latest_work_category_id' => null,
            'title' => $title,
            'slug' => Str::slug($title).'-'.fake()->unique()->numberBetween(1, 999999),
            'short_description' => fake()->sentence(12),
            'details' => null,
            'image' => null,
            'project_url' => fake()->url(),
            'technologies' => [fake()->word(), fake()->word()],
            'is_featured' => false,
            'is_visible' => true,
            'sort_order' => 0,
        ];
    }

    public function visible(): static
    {
        return $this->state(fn (array $attributes): array => ['is_visible' => true]);
    }

    public function hidden(): static
    {
        return $this->state(fn (array $attributes): array => ['is_visible' => false]);
    }

    public function featured(): static
    {
        return $this->state(fn (array $attributes): array => ['is_featured' => true]);
    }

    public function untyped(): static
    {
        return $this->state(fn (array $attributes): array => ['latest_work_category_id' => null]);
    }
}
