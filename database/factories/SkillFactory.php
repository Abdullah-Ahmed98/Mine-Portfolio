<?php

namespace Database\Factories;

use App\Models\Skill;
use App\Models\SkillCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Skill>
 */
class SkillFactory extends Factory
{
    /**
     * A skill always belongs to a group, so one is created unless the caller
     * supplies its own.
     */
    public function configure(): static
    {
        return $this->afterMaking(function (Skill $skill): void {
            $skill->skill_category_id ??= SkillCategory::factory()->create()->id;
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
            'name' => fake()->unique()->word(),
            'level' => null,
            'is_visible' => true,
            'sort_order' => 0,
        ];
    }

    public function withLevel(int $level): static
    {
        return $this->state(fn (array $attributes): array => ['level' => $level]);
    }

    public function hidden(): static
    {
        return $this->state(fn (array $attributes): array => ['is_visible' => false]);
    }

    public function forCategory(SkillCategory $category): static
    {
        return $this->for($category, 'category');
    }
}
