<?php

namespace Database\Factories;

use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
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
            'title' => $title,
            'slug' => Str::slug($title).'-'.fake()->unique()->numberBetween(1, 999999),
            'client_name' => fake()->company(),
            'client_role' => fake()->jobTitle(),
            'short_description' => fake()->sentence(14),
            'full_description' => null,
            'featured_image' => null,
            'project_url' => fake()->url(),
            'github_url' => 'https://github.com/'.fake()->userName().'/'.Str::slug($title),
            'completed_at' => fake()->dateTimeBetween('-4 years', 'now'),
            'technologies' => [fake()->word(), fake()->word(), fake()->word()],
            'is_featured' => false,
            'is_published' => true,
            'sort_order' => 0,
        ];
    }

    public function featured(): static
    {
        return $this->state(fn (array $attributes): array => ['is_featured' => true]);
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes): array => ['is_published' => false]);
    }
}
