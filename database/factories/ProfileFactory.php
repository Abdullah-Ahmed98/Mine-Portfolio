<?php

namespace Database\Factories;

use App\Models\Profile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Profile>
 */
class ProfileFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'full_name' => fake()->name(),
            'title' => fake()->jobTitle(),
            'role_short' => fake()->jobTitle(),
            'short_intro' => fake()->sentence(12),
            'full_description' => fake()->paragraph()."\n\n".fake()->paragraph(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'location' => fake()->city().', '.fake()->country(),
            'years_experience' => fake()->numberBetween(2, 15).'+',
            'availability_text' => 'Available for new projects',
            'availability_status' => true,
            'profile_image' => null,
            'cv_path' => null,
        ];
    }

    public function withoutCv(): static
    {
        return $this->state(fn (array $attributes): array => ['cv_path' => null]);
    }

    public function unavailable(): static
    {
        return $this->state(fn (array $attributes): array => ['availability_status' => false]);
    }
}
