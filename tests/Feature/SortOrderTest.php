<?php

namespace Tests\Feature;

use App\Models\EducationEntry;
use App\Models\Project;
use App\Models\ProjectImage;
use App\Models\Skill;
use App\Models\SkillCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class SortOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_empty_list_starts_at_zero(): void
    {
        $this->assertSame(0, EducationEntry::nextSortOrder());
    }

    public function test_the_next_position_follows_the_current_maximum(): void
    {
        EducationEntry::factory()->create(['sort_order' => 0]);
        EducationEntry::factory()->create(['sort_order' => 1]);

        $this->assertSame(2, EducationEntry::nextSortOrder());
    }

    public function test_a_gap_in_the_sequence_does_not_reuse_a_position(): void
    {
        EducationEntry::factory()->create(['sort_order' => 0]);
        EducationEntry::factory()->create(['sort_order' => 7]);

        $this->assertSame(8, EducationEntry::nextSortOrder());
    }

    public function test_the_next_position_can_be_scoped_to_one_parent(): void
    {
        $design = SkillCategory::factory()->create();
        $development = SkillCategory::factory()->create();

        Skill::factory()->for($design, 'category')->create(['sort_order' => 0]);
        Skill::factory()->for($development, 'category')->create(['sort_order' => 0]);
        Skill::factory()->for($development, 'category')->create(['sort_order' => 1]);

        $this->assertSame(1, Skill::nextSortOrder($design->skills()));
        $this->assertSame(2, Skill::nextSortOrder($development->skills()));
        $this->assertSame(2, Skill::nextSortOrder(Skill::query()->where('skill_category_id', $development->id)));
    }

    public function test_the_first_record_created_through_the_admin_gets_position_zero(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/admin/education', ['title' => 'B.S. in Computer Science'])
            ->assertSessionHasNoErrors();

        $this->assertSame(0, EducationEntry::query()->sole()->sort_order);
    }

    public function test_successive_records_land_after_the_last_one(): void
    {
        $this->actingAs(User::factory()->create());

        foreach (['First', 'Second', 'Third'] as $title) {
            $this->post('/admin/education', ['title' => $title])->assertSessionHasNoErrors();
        }

        $this->assertSame(
            ['First', 'Second', 'Third'],
            EducationEntry::query()->orderBy('sort_order')->pluck('title')->all(),
        );
    }

    public function test_the_first_gallery_image_gets_position_zero(): void
    {
        $this->actingAs(User::factory()->create());

        $project = Project::factory()->create();

        $this->post("/admin/projects/{$project->slug}/images", [
            'images' => [UploadedFile::fake()->image('one.jpg', 900, 600)],
        ])->assertSessionHasNoErrors();

        $this->assertSame(0, ProjectImage::query()->sole()->sort_order);
    }
}
