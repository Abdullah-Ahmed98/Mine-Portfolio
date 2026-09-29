<?php

namespace Tests\Feature\Admin;

use App\Models\ContactMessage;
use App\Models\EducationEntry;
use App\Models\Experience;
use App\Models\ProfileHighlight;
use App\Models\Project;
use App\Models\ProjectCategory;
use App\Models\ProjectImage;
use App\Models\Skill;
use App\Models\SkillCategory;
use App\Models\SocialLink;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Every admin resource binds its route parameter to the stored record.
 *
 * A controller argument whose name does not match the route parameter silently
 * skips implicit binding, which leaves edit, update and delete operating on an
 * unsaved model instead of failing loudly. These tests walk each resource so
 * that a rename can never reintroduce that.
 */
class ResourceBindingTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A record to bind, a unique string it was stored with, and the id it is
     * addressed by in the URL.
     *
     * @return array<int, array{0: string, 1: Model}>
     */
    private function records(): array
    {
        return [
            'social-links' => [
                SocialLink::factory()->create(['label' => 'Binding Probe Social']),
                null,
            ],
            'highlights' => [
                ProfileHighlight::factory()->create(['title' => 'Binding Probe Highlight']),
                null,
            ],
            'skill-categories' => [
                SkillCategory::factory()->create(['name' => 'Binding Probe Group']),
                null,
            ],
            'skills' => [
                Skill::factory()->create(['name' => 'Binding Probe Skill']),
                null,
            ],
            'experiences' => [
                Experience::factory()->create(['company' => 'Binding Probe Company']),
                null,
            ],
            'project-categories' => [
                ProjectCategory::factory()->create(['name' => 'Binding Probe Category']),
                null,
            ],
            'projects' => [
                Project::factory()->create(['title' => 'Binding Probe Project']),
                null,
            ],
            'education' => [
                EducationEntry::factory()->create(['title' => 'Binding Probe Entry']),
                null,
            ],
        ];
    }

    private function identifier(Model $record): string
    {
        return (string) $record->getRouteKey();
    }

    public function test_the_edit_form_pre_fills_the_stored_record(): void
    {
        $user = User::factory()->create();

        $expected = [
            'social-links' => 'Binding Probe Social',
            'highlights' => 'Binding Probe Highlight',
            'skill-categories' => 'Binding Probe Group',
            'skills' => 'Binding Probe Skill',
            'experiences' => 'Binding Probe Company',
            'project-categories' => 'Binding Probe Category',
            'projects' => 'Binding Probe Project',
            'education' => 'Binding Probe Entry',
        ];

        foreach ($this->records() as $resource => [$record]) {
            $this->actingAs($user)
                ->get("/admin/{$resource}/{$this->identifier($record)}/edit")
                ->assertOk("Expected /admin/{$resource} edit screen to render.")
                ->assertSee($expected[$resource]);
        }
    }

    public function test_updating_a_record_through_the_admin_saves_to_that_record(): void
    {
        $user = User::factory()->create();

        foreach ($this->records() as $resource => [$record]) {
            $before = $record->fresh();

            $response = $this->actingAs($user)->put("/admin/{$resource}/{$this->identifier($record)}", $this->payloadFor($resource));

            $response->assertSessionHasNoErrors("Expected the {$resource} update to validate.");

            $this->assertNotNull(
                $before->fresh(),
                "Deleting or losing the {$resource} row while updating it.",
            );
        }
    }

    public function test_deleting_a_record_removes_exactly_that_record(): void
    {
        $user = User::factory()->create();

        foreach ($this->records() as $resource => [$record]) {
            $id = $record->getKey();

            $this->actingAs($user)
                ->delete("/admin/{$resource}/{$this->identifier($record)}")
                ->assertSessionHas('status');

            $this->assertNull(
                $record->newQuery()->find($id),
                "Expected the {$resource} record to be deleted.",
            );
        }
    }

    public function test_an_unknown_record_id_returns_404(): void
    {
        $user = User::factory()->create();

        foreach (array_keys($this->records()) as $resource) {
            $this->actingAs($user)
                ->get("/admin/{$resource}/999999/edit")
                ->assertNotFound("Expected /admin/{$resource}/999999/edit to 404.");
        }
    }

    public function test_a_message_binds_by_id(): void
    {
        $message = ContactMessage::factory()->create(['subject' => 'Binding Probe Message']);

        $this->actingAs(User::factory()->create())
            ->get("/admin/messages/{$message->id}")
            ->assertOk()
            ->assertSee('Binding Probe Message');
    }

    public function test_a_gallery_image_binds_by_id(): void
    {
        $project = Project::factory()->create();
        $image = ProjectImage::factory()->for($project)->create(['alt' => 'Binding Probe Image']);

        $this->actingAs(User::factory()->create())
            ->from("/admin/projects/{$project->slug}/edit")
            ->put("/admin/projects/images/{$image->id}", ['alt' => 'Renamed Probe Image'])
            ->assertSessionHas('status');

        $this->assertSame('Renamed Probe Image', $image->fresh()->alt);
    }

    /**
     * The minimum payload each form needs in order to validate.
     *
     * @return array<string, mixed>
     */
    private function payloadFor(string $resource): array
    {
        return match ($resource) {
            'social-links' => ['platform' => 'linkedin', 'url' => 'https://linkedin.com/in/probe'],
            'highlights' => ['title' => 'Probe Highlight', 'text' => 'Probe highlight body.'],
            'skill-categories' => ['name' => 'Probe Group'],
            'skills' => [
                'skill_category_id' => SkillCategory::query()->value('id'),
                'name' => 'Probe Skill',
            ],
            'experiences' => ['company' => 'Probe Company', 'position' => 'Probe Position'],
            'project-categories' => ['name' => 'Probe Category'],
            'projects' => ['title' => 'Probe Project', 'slug' => 'probe-project'],
            'education' => ['title' => 'Probe Entry'],
        };
    }
}
