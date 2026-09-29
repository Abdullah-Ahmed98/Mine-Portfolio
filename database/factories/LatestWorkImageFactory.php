<?php

namespace Database\Factories;

use App\Models\LatestWorkImage;
use App\Models\LatestWorkItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LatestWorkImage>
 */
class LatestWorkImageFactory extends Factory
{
    /**
     * A gallery image always belongs to an item, so one is created unless the
     * caller supplies its own.
     */
    public function configure(): static
    {
        return $this->afterMaking(function (LatestWorkImage $image): void {
            $image->latest_work_item_id ??= LatestWorkItem::factory()->create()->id;
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
            'path' => 'portfolio/latest-work/'.fake()->unique()->uuid().'.jpg',
            'alt' => fake()->sentence(4),
            'is_cover' => false,
            'sort_order' => 0,
        ];
    }

    public function cover(): static
    {
        return $this->state(fn (array $attributes): array => ['is_cover' => true]);
    }
}
