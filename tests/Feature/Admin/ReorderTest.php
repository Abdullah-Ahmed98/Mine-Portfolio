<?php

namespace Tests\Feature\Admin;

use App\Models\EducationEntry;
use App\Models\Experience;
use App\Models\Project;
use App\Models\ProjectImage;
use App\Models\Skill;
use App\Models\SkillCategory;
use App\Models\SocialLink;
use App\Models\User;
use App\Services\ReorderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ReorderTest extends TestCase
{
    use RefreshDatabase;

    public function test_moving_a_record_down_swaps_it_with_the_next_one(): void
    {
        $first = EducationEntry::factory()->create(['sort_order' => 0]);
        $second = EducationEntry::factory()->create(['sort_order' => 1]);
        $third = EducationEntry::factory()->create(['sort_order' => 2]);

        $this->actingAs(User::factory()->create())
            ->from('/admin/education')
            ->post("/admin/reorder/education/{$first->id}", ['direction' => 'down'])
            ->assertRedirect('/admin/education')
            ->assertSessionHas('status');

        $this->assertSame(
            [$second->id, $first->id, $third->id],
            EducationEntry::query()->orderBy('sort_order')->pluck('id')->all(),
        );
    }

    public function test_moving_a_record_up_swaps_it_with_the_previous_one(): void
    {
        $first = EducationEntry::factory()->create(['sort_order' => 0]);
        $second = EducationEntry::factory()->create(['sort_order' => 1]);

        $this->actingAs(User::factory()->create())
            ->post("/admin/reorder/education/{$second->id}", ['direction' => 'up']);

        $this->assertSame(
            [$second->id, $first->id],
            EducationEntry::query()->orderBy('sort_order')->pluck('id')->all(),
        );
    }

    public function test_moving_the_first_record_up_leaves_the_order_unchanged(): void
    {
        $first = EducationEntry::factory()->create(['sort_order' => 0]);
        $second = EducationEntry::factory()->create(['sort_order' => 1]);

        $this->actingAs(User::factory()->create())
            ->post("/admin/reorder/education/{$first->id}", ['direction' => 'up']);

        $this->assertSame(
            [$first->id, $second->id],
            EducationEntry::query()->orderBy('sort_order')->pluck('id')->all(),
        );
    }

    public function test_moving_the_last_record_down_leaves_the_order_unchanged(): void
    {
        $first = EducationEntry::factory()->create(['sort_order' => 0]);
        $last = EducationEntry::factory()->create(['sort_order' => 1]);

        $this->actingAs(User::factory()->create())
            ->post("/admin/reorder/education/{$last->id}", ['direction' => 'down']);

        $this->assertSame(
            [$first->id, $last->id],
            EducationEntry::query()->orderBy('sort_order')->pluck('id')->all(),
        );
    }

    public function test_moving_up_and_then_down_restores_the_original_order(): void
    {
        $first = EducationEntry::factory()->create(['sort_order' => 0]);
        $second = EducationEntry::factory()->create(['sort_order' => 1]);
        $user = User::factory()->create();

        $this->actingAs($user)->post("/admin/reorder/education/{$first->id}", ['direction' => 'down']);
        $this->actingAs($user)->post("/admin/reorder/education/{$first->id}", ['direction' => 'up']);

        $this->assertSame(
            [$first->id, $second->id],
            EducationEntry::query()->orderBy('sort_order')->pluck('id')->all(),
        );
    }

    public function test_reordering_a_skill_does_not_touch_skills_in_another_group(): void
    {
        $design = SkillCategory::factory()->create();
        $development = SkillCategory::factory()->create();

        $designFirst = Skill::factory()->for($design, 'category')->create(['sort_order' => 0]);
        $designSecond = Skill::factory()->for($design, 'category')->create(['sort_order' => 1]);
        $developmentSkill = Skill::factory()->for($development, 'category')->create(['sort_order' => 0]);

        $this->actingAs(User::factory()->create())
            ->post("/admin/reorder/skills/{$designFirst->id}", ['direction' => 'down']);

        $this->assertSame(
            [$designSecond->id, $designFirst->id],
            $design->skills()->pluck('id')->all(),
        );
        $this->assertSame(
            [$developmentSkill->id],
            $development->skills()->pluck('id')->all(),
        );
    }

    public function test_reordering_a_gallery_image_does_not_touch_another_project(): void
    {
        $project = Project::factory()->create();
        $otherProject = Project::factory()->create();

        $projectImage = ProjectImage::factory()->for($project)->create(['sort_order' => 0]);
        $projectSecondImage = ProjectImage::factory()->for($project)->create(['sort_order' => 1]);
        $otherImage = ProjectImage::factory()->for($otherProject)->create(['sort_order' => 0]);

        $this->actingAs(User::factory()->create())
            ->post("/admin/reorder/project-images/{$projectImage->id}", ['direction' => 'down']);

        $this->assertSame(
            [$projectSecondImage->id, $projectImage->id],
            $project->images()->pluck('id')->all(),
        );
        $this->assertSame(
            [$otherImage->id],
            $otherProject->images()->pluck('id')->all(),
        );
    }

    /**
     * @return array<int, array<int, string>>
     */
    public static function globalLists(): array
    {
        return [
            ['social-links'],
            ['experiences'],
            ['projects'],
        ];
    }

    #[DataProvider('globalLists')]
    public function test_global_lists_reorder_across_the_whole_table(string $type): void
    {
        $model = match ($type) {
            'social-links' => SocialLink::factory(),
            'experiences' => Experience::factory(),
            'projects' => Project::factory(),
        };

        $first = $model->create(['sort_order' => 0]);
        $second = $model->create(['sort_order' => 1]);

        $this->actingAs(User::factory()->create())
            ->post("/admin/reorder/{$type}/{$first->getKey()}", ['direction' => 'down']);

        $this->assertSame(
            [$second->getKey(), $first->getKey()],
            $first->newQuery()->orderBy('sort_order')->pluck('id')->all(),
        );
    }

    public function test_the_service_rejects_an_unknown_type(): void
    {
        $this->expectException(InvalidArgumentException::class);

        app(ReorderService::class)->move('users', 1, 'up');
    }

    public function test_the_service_reports_no_move_for_a_missing_record(): void
    {
        $this->assertFalse(app(ReorderService::class)->move('education', 999, 'up'));
    }
}
