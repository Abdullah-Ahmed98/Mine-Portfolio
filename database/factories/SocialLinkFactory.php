<?php

namespace Database\Factories;

use App\Models\SocialLink;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<SocialLink>
 */
class SocialLinkFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'platform' => 'custom',
            'label' => Str::headline(fake()->word()),
            'url' => fake()->url(),
            'icon' => null,
            'is_visible' => true,
            'sort_order' => 0,
        ];
    }

    public function forPlatform(string $platform, ?string $url = null): static
    {
        return $this->state(fn (array $attributes): array => [
            'platform' => $platform,
            'url' => $url ?? fake()->url(),
        ]);
    }

    /**
     * A platform the admin has added but not filled in yet.
     */
    public function withoutUrl(): static
    {
        return $this->state(fn (array $attributes): array => ['url' => null]);
    }

    public function hidden(): static
    {
        return $this->state(fn (array $attributes): array => ['is_visible' => false]);
    }
}
