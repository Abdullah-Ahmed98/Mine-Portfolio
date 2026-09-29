<?php

namespace Database\Factories;

use App\Models\Setting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Setting>
 */
class SettingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'key' => 'nav.'.fake()->unique()->slug(2),
            'value' => fake()->sentence(3),
            'group' => 'general',
            'type' => 'text',
            'label' => null,
            'sort_order' => 0,
        ];
    }

    public function inGroup(string $group, ?string $label = null): static
    {
        return $this->state(fn (array $attributes): array => [
            'group' => $group,
            'label' => $label,
        ]);
    }
}
