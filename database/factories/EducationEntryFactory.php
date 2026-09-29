<?php

namespace Database\Factories;

use App\Models\EducationEntry;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EducationEntry>
 */
class EducationEntryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->randomElement([
                'B.S. in Computer Science',
                'B.A. in Graphic Design',
                'Webflow Certification',
            ]),
            'institution' => fake()->company().' University',
            'meta' => null,
            'description' => fake()->sentence(12),
            'start_date' => fake()->dateTimeBetween('-10 years', '-5 years'),
            'end_date' => fake()->dateTimeBetween('-5 years', 'now'),
            'sort_order' => 0,
        ];
    }

    public function achievement(string $title, string $meta): static
    {
        return $this->state(fn (array $attributes): array => [
            'title' => $title,
            'institution' => null,
            'meta' => $meta,
            'start_date' => null,
            'end_date' => null,
        ]);
    }
}
