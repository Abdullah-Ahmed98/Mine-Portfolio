<?php

namespace Database\Factories;

use App\Models\Profile;
use App\Models\ProfileHighlight;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProfileHighlight>
 */
class ProfileHighlightFactory extends Factory
{
    /**
     * A highlight always belongs to the profile, so one is created unless the
     * caller supplies its own.
     */
    public function configure(): static
    {
        return $this->afterMaking(function (ProfileHighlight $highlight): void {
            $highlight->profile_id ??= Profile::factory()->create()->id;
        });
    }

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->word(),
            'text' => fake()->sentence(10),
            'sort_order' => 0,
        ];
    }
}
