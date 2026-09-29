<?php

namespace Database\Factories;

use App\Models\Experience;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Experience>
 */
class ExperienceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company' => fake()->company(),
            'position' => fake()->jobTitle(),
            'location' => fake()->city(),
            'start_date' => fake()->dateTimeBetween('-6 years', '-1 year'),
            'end_date' => fake()->dateTimeBetween('-1 year', 'now'),
            'is_current' => false,
            'date_label' => null,
            'description' => fake()->sentence(14),
            'responsibilities' => [fake()->sentence(8), fake()->sentence(8)],
            'technologies' => [fake()->word(), fake()->word()],
            'company_url' => fake()->url(),
            'sort_order' => 0,
        ];
    }

    public function current(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_current' => true,
            'end_date' => null,
        ]);
    }

    public function labelled(string $label): static
    {
        return $this->state(fn (array $attributes): array => ['date_label' => $label]);
    }
}
