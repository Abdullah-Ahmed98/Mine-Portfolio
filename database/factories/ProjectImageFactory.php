<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\ProjectImage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProjectImage>
 */
class ProjectImageFactory extends Factory
{
    /**
     * A gallery image always belongs to a project, so one is created unless
     * the caller supplies its own.
     */
    public function configure(): static
    {
        return $this->afterMaking(function (ProjectImage $image): void {
            $image->project_id ??= Project::factory()->create()->id;
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
            'path' => 'portfolio/projects/'.fake()->unique()->uuid().'.jpg',
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
