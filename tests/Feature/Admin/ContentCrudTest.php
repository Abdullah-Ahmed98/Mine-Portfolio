<?php

namespace Tests\Feature\Admin;

use App\Models\EducationEntry;
use App\Models\Experience;
use App\Models\Profile;
use App\Models\Project;
use App\Models\ProjectCategory;
use App\Models\Skill;
use App\Models\SkillCategory;
use App\Models\SocialLink;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ContentCrudTest extends TestCase
{
    use RefreshDatabase;

    protected ProjectCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        // Every test in this class drives the CMS from inside the admin, so the
        // panel is signed in for the whole test.
        $this->actingAs(User::factory()->create());

        // A published project has to sit in a showcase section, so the project
        // tests all need a category to file their work under.
        $this->category = ProjectCategory::factory()->create(['name' => 'Development']);
    }

    // --- experiences ------------------------------------------------------

    public function test_creating_an_experience_stores_it_and_redirects_to_the_list(): void
    {
        $this->post('/admin/experiences', [
            'company' => 'Northwind Traders',
            'position' => 'Interface Engineer',
            'start_date' => '2023-04-01',
            'description' => 'Built and maintained the design system.',
            'responsibilities' => "Shipped the component library\nReviewed pull requests",
            'technologies' => 'Blade, Alpine, Vite',
        ])
            ->assertRedirect(route('admin.experiences.index'))
            ->assertSessionHas('status');

        $experience = Experience::query()->sole();

        $this->assertSame('Northwind Traders', $experience->company);
        $this->assertSame(['Shipped the component library', 'Reviewed pull requests'], $experience->responsibilities);
        $this->assertSame(['Blade', 'Alpine', 'Vite'], $experience->technologies);
    }

    /**
     * The tag inputs are free text, so a stray comma, a blank line or a
     * duplicate must not become its own bullet.
     */
    public function test_tag_lists_are_split_trimmed_and_de_duplicated(): void
    {
        $this->post('/admin/experiences', [
            'company' => 'Northwind Traders',
            'position' => 'Interface Engineer',
            'technologies' => " Blade ,, Alpine \n Blade ,  Vite ",
        ])->assertSessionHasNoErrors();

        $this->assertSame(
            ['Blade', 'Alpine', 'Vite'],
            Experience::query()->sole()->technologies,
        );
    }

    public function test_marking_an_experience_current_clears_its_end_date(): void
    {
        $this->post('/admin/experiences', [
            'company' => 'Northwind Traders',
            'position' => 'Interface Engineer',
            'start_date' => '2023-04-01',
            'end_date' => '2024-01-01',
            'is_current' => '1',
        ])->assertSessionHasNoErrors();

        $experience = Experience::query()->sole();

        $this->assertTrue($experience->is_current);
        $this->assertNull($experience->end_date);
    }

    /**
     * An unchecked checkbox is absent from the payload, so clearing the flag
     * has to work without sending a false value.
     */
    public function test_clearing_the_current_flag_on_update_persists(): void
    {
        $experience = Experience::factory()->current()->create();

        $this->put("/admin/experiences/{$experience->id}", [
            'company' => $experience->company,
            'position' => $experience->position,
        ])->assertSessionHasNoErrors();

        $this->assertFalse($experience->fresh()->is_current);
    }

    public function test_an_experience_requiring_both_a_company_and_a_position_is_rejected(): void
    {
        $this->from('/admin/experiences/create')
            ->post('/admin/experiences', ['company' => '', 'position' => ''])
            ->assertSessionHasErrors(['company', 'position']);

        $this->assertSame(0, Experience::query()->count());
    }

    public function test_an_end_date_before_the_start_date_is_rejected(): void
    {
        $this->from('/admin/experiences/create')
            ->post('/admin/experiences', [
                'company' => 'Northwind Traders',
                'position' => 'Interface Engineer',
                'start_date' => '2024-01-01',
                'end_date' => '2023-01-01',
            ])
            ->assertSessionHasErrors('end_date');

        $this->assertSame(0, Experience::query()->count());
    }

    public function test_a_company_url_that_is_not_a_url_is_rejected(): void
    {
        $this->from('/admin/experiences/create')
            ->post('/admin/experiences', [
                'company' => 'Northwind Traders',
                'position' => 'Interface Engineer',
                'company_url' => 'javascript:alert(1)',
            ])
            ->assertSessionHasErrors('company_url');
    }

    public function test_updating_an_experience_replaces_its_tag_lists(): void
    {
        $experience = Experience::factory()->create([
            'technologies' => ['Legacy'],
            'responsibilities' => ['Old duty'],
        ]);

        $this->put("/admin/experiences/{$experience->id}", [
            'company' => 'Northwind Traders',
            'position' => 'Interface Engineer',
            'technologies' => 'Blade, Alpine',
            'responsibilities' => 'New duty',
        ])->assertSessionHasNoErrors();

        $this->assertSame(['Blade', 'Alpine'], $experience->fresh()->technologies);
        $this->assertSame(['New duty'], $experience->fresh()->responsibilities);
    }

    public function test_deleting_an_experience_removes_it(): void
    {
        $experience = Experience::factory()->create();

        $this
            ->delete("/admin/experiences/{$experience->id}")
            ->assertSessionHas('status');

        $this->assertDatabaseMissing('experiences', ['id' => $experience->id]);
    }

    // --- projects ---------------------------------------------------------

    public function test_creating_a_project_derives_a_slug_from_the_title(): void
    {
        $this->post('/admin/projects', [
            'title' => 'Traffic Sign Analytics',
            'short_description' => 'A study of sign recognition.',
            'project_category_id' => $this->category->id,
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('projects', ['slug' => 'traffic-sign-analytics']);
    }

    public function test_a_duplicate_title_gets_a_numbered_slug(): void
    {
        Project::factory()->for($this->category, 'category')->create(['title' => 'Case Study', 'slug' => 'case-study']);

        $this->post('/admin/projects', ['title' => 'Case Study', 'project_category_id' => $this->category->id])->assertSessionHasNoErrors();
        $this->post('/admin/projects', ['title' => 'Case Study', 'project_category_id' => $this->category->id])->assertSessionHasNoErrors();

        $this->assertSame(
            ['case-study', 'case-study-2', 'case-study-3'],
            Project::query()->orderBy('slug')->pluck('slug')->all(),
        );
    }

    public function test_keeping_the_same_slug_on_update_does_not_collide_with_itself(): void
    {
        $project = Project::factory()->create(['title' => 'Case Study', 'slug' => 'case-study']);

        $this->put("/admin/projects/{$project->slug}", [
            'title' => 'Case Study',
            'slug' => 'case-study',
            'short_description' => 'Updated summary.',
        ])->assertSessionHasNoErrors();

        $this->assertSame('case-study', $project->fresh()->slug);
    }

    /**
     * A published project's URL is already in the sitemap and may have been
     * shared, so renaming the project must not move it.
     */
    public function test_editing_a_title_without_touching_the_slug_keeps_the_url(): void
    {
        $project = Project::factory()->for($this->category, 'category')->create([
            'title' => 'Traffic Sign Analytics',
            'slug' => 'traffic-sign-analytics',
        ]);

        $this->put('/admin/projects/traffic-sign-analytics', [
            'title' => 'Traffic Sign Recognition Study',
            'short_description' => 'A revised summary.',
            'is_published' => '1',
            'project_category_id' => $this->category->id,
        ])->assertSessionHasNoErrors();

        $this->assertSame('traffic-sign-analytics', $project->fresh()->slug);
        $this->assertSame('Traffic Sign Recognition Study', $project->fresh()->title);

        // The project has no page of its own; the site is one document, so the
        // only thing that can move is its anchor on that page.
        $this->get('/')->assertOk()->assertSee('id="'.$project->fresh()->anchor().'"', escape: false);
    }

    public function test_clearing_the_slug_field_regenerates_it_from_the_title(): void
    {
        $project = Project::factory()->create([
            'title' => 'Traffic Sign Analytics',
            'slug' => 'an-old-slug',
        ]);

        $this->put('/admin/projects/an-old-slug', [
            'title' => 'Traffic Sign Analytics',
            'slug' => '',
        ])->assertSessionHasNoErrors();

        $this->assertSame('traffic-sign-analytics', $project->fresh()->slug);
    }

    public function test_requesting_a_taken_slug_gets_a_suffix(): void
    {
        Project::factory()->create(['slug' => 'shared-slug']);

        $project = Project::factory()->create(['slug' => 'original-slug']);

        $this->put('/admin/projects/original-slug', [
            'title' => $project->title,
            'slug' => 'shared-slug',
        ])->assertSessionHasNoErrors();

        $this->assertSame('shared-slug-2', $project->fresh()->slug);
    }

    public function test_a_new_project_is_published_and_unfeatured_by_default(): void
    {
        $this->post('/admin/projects', [
            'title' => 'Quiet Build',
            'project_category_id' => $this->category->id,
        ])->assertSessionHasNoErrors();

        $project = Project::query()->sole();

        $this->assertTrue($project->is_published);
        $this->assertFalse($project->is_featured);
    }

    /**
     * @return array<int, array<int, string>>
     */
    public static function booleanFields(): array
    {
        return [
            ['is_featured'],
            ['is_published'],
        ];
    }

    #[DataProvider('booleanFields')]
    public function test_clearing_a_project_boolean_persists(string $field): void
    {
        $project = Project::factory()->for($this->category, 'category')->create([$field => true]);

        $this->assertTrue($project->{$field});

        // Every flag but the one under test is sent as checked, so the only
        // thing missing from the payload is the flag being cleared.
        $this->put("/admin/projects/{$project->slug}", [
            'title' => $project->title,
            'is_featured' => $field === 'is_featured' ? null : '1',
            'is_published' => $field === 'is_published' ? null : '1',
            'project_category_id' => $this->category->id,
        ])->assertSessionHasNoErrors();

        $this->assertFalse($project->fresh()->{$field});
    }

    public function test_a_project_needs_a_title(): void
    {
        $this->from('/admin/projects/create')
            ->post('/admin/projects', ['title' => ''])
            ->assertSessionHasErrors('title');

        $this->assertSame(0, Project::query()->count());
    }

    public function test_a_project_must_point_at_an_existing_category(): void
    {
        $this->from('/admin/projects/create')
            ->post('/admin/projects', ['title' => 'Case Study', 'project_category_id' => 999])
            ->assertSessionHasErrors('project_category_id');
    }

    public function test_unpublishing_a_project_hides_it_from_the_public_site(): void
    {
        $project = Project::factory()->for($this->category, 'category')->create(['title' => 'Shipped Build']);

        $this->get('/')->assertOk()->assertSee('Shipped Build');

        // Unpublished, so a section is no longer required to keep it valid.
        $this->put("/admin/projects/{$project->slug}", [
            'title' => $project->title,
            'short_description' => $project->short_description,
        ])->assertSessionHasNoErrors();

        $this->get('/')->assertOk()->assertDontSee('Shipped Build');
    }

    public function test_deleting_a_project_removes_its_gallery_images(): void
    {
        $project = Project::factory()->create();

        $project->images()->create([
            'path' => 'portfolio/projects/x.jpg',
            'alt' => 'A screenshot',
            'is_cover' => false,
            'sort_order' => 0,
        ]);

        $this
            ->delete("/admin/projects/{$project->slug}")
            ->assertSessionHas('status');

        $this->assertDatabaseMissing('projects', ['id' => $project->id]);
        $this->assertDatabaseMissing('project_images', ['project_id' => $project->id]);
    }

    // --- skills -----------------------------------------------------------

    public function test_creating_a_skill_requires_an_existing_group(): void
    {
        $this->from('/admin/skills/create')
            ->post('/admin/skills', ['skill_category_id' => 999, 'name' => 'Blade'])
            ->assertSessionHasErrors('skill_category_id');

        $this->assertSame(0, Skill::query()->count());
    }

    public function test_a_skill_level_may_be_left_blank(): void
    {
        $category = SkillCategory::factory()->create();

        $this->post('/admin/skills', [
            'skill_category_id' => $category->id,
            'name' => 'Blade',
            'level' => '',
        ])->assertSessionHasNoErrors();

        $skill = Skill::query()->sole();

        $this->assertSame('Blade', $skill->name);
        $this->assertNull($skill->level);
    }

    public function test_a_skill_level_must_be_a_percentage(): void
    {
        $category = SkillCategory::factory()->create();

        $this->from('/admin/skills/create')
            ->post('/admin/skills', [
                'skill_category_id' => $category->id,
                'name' => 'Blade',
                'level' => 140,
            ])
            ->assertSessionHasErrors('level');
    }

    public function test_hiding_a_skill_removes_it_from_the_public_skills_section(): void
    {
        Profile::factory()->create();

        $category = SkillCategory::factory()->create();
        Skill::factory()->for($category, 'category')->create(['name' => 'Webflow']);
        $hidden = Skill::factory()->for($category, 'category')->create(['name' => 'Retired Tool']);

        $this->get('/')->assertOk()->assertSee('Webflow')->assertSee('Retired Tool');

        $this->put("/admin/skills/{$hidden->id}", [
            'skill_category_id' => $category->id,
            'name' => $hidden->name,
        ])->assertSessionHasNoErrors();

        $this->get('/')->assertOk()->assertSee('Webflow')->assertDontSee('Retired Tool');
    }

    public function test_a_group_with_no_visible_skills_is_dropped_from_the_skills_section(): void
    {
        Profile::factory()->create();

        $visible = SkillCategory::factory()->create(['name' => 'Design']);
        Skill::factory()->for($visible, 'category')->create();

        $empty = SkillCategory::factory()->create(['name' => 'Retired Group']);
        Skill::factory()->for($empty, 'category')->hidden()->create();

        $this->get('/')
            ->assertOk()
            ->assertSee('Design')
            ->assertDontSee('Retired Group');
    }

    // --- categories -------------------------------------------------------

    public function test_a_category_slug_is_derived_from_its_name(): void
    {
        $this->post('/admin/skill-categories', ['name' => 'Front-End'])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('skill_categories', ['name' => 'Front-End', 'slug' => 'front-end']);
    }

    public function test_two_categories_cannot_share_a_slug(): void
    {
        ProjectCategory::factory()->create(['slug' => 'web']);

        $this->from('/admin/project-categories/create')
            ->post('/admin/project-categories', ['name' => 'Web', 'slug' => 'web'])
            ->assertSessionHasErrors('slug');

        $this->assertSame(1, ProjectCategory::query()->where('slug', 'web')->count());
    }

    public function test_a_category_may_keep_its_own_slug_on_update(): void
    {
        $category = SkillCategory::factory()->create(['name' => 'Design', 'slug' => 'design']);

        $this->put("/admin/skill-categories/{$category->id}", [
            'name' => 'Design',
            'slug' => 'design',
        ])->assertSessionHasNoErrors();

        $this->assertSame('design', $category->fresh()->slug);
    }

    public function test_deleting_a_group_with_skills_orphans_nothing(): void
    {
        $category = SkillCategory::factory()->create();
        Skill::factory()->for($category, 'category')->create();

        $this
            ->delete("/admin/skill-categories/{$category->id}")
            ->assertSessionHas('status');

        $this->assertDatabaseMissing('skill_categories', ['id' => $category->id]);
        $this->assertSame(0, Skill::query()->count());
    }

    // --- social links, highlights, education -------------------------------

    public function test_a_social_link_may_be_saved_without_a_url(): void
    {
        $this->post('/admin/social-links', ['platform' => 'github', 'url' => ''])
            ->assertSessionHasNoErrors();

        $link = SocialLink::query()->sole();

        $this->assertSame('github', $link->platform);
        $this->assertNull($link->url);
        $this->assertFalse($link->isRenderable());
    }

    public function test_an_unknown_platform_is_rejected(): void
    {
        $this->from('/admin/social-links/create')
            ->post('/admin/social-links', ['platform' => 'myspace', 'url' => 'https://myspace.com/ada'])
            ->assertSessionHasErrors('platform');
    }

    public function test_a_social_link_url_must_be_a_url(): void
    {
        $this->from('/admin/social-links/create')
            ->post('/admin/social-links', ['platform' => 'github', 'url' => 'javascript:alert(1)'])
            ->assertSessionHasErrors('url');
    }

    public function test_creating_a_highlight(): void
    {
        $profile = Profile::factory()->create();

        $this->post('/admin/highlights', [
            'title' => 'Design systems',
            'text' => 'Built a shared library for three product teams.',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('profile_highlights', [
            'profile_id' => $profile->id,
            'title' => 'Design systems',
        ]);
    }

    public function test_a_highlight_needs_a_title_and_body(): void
    {
        $this->from('/admin/highlights/create')
            ->post('/admin/highlights', ['title' => '', 'text' => ''])
            ->assertSessionHasErrors(['title', 'text']);
    }

    public function test_creating_an_education_entry(): void
    {
        $this->post('/admin/education', [
            'title' => 'B.S. in Computer Science',
            'institution' => 'Example University',
            'start_date' => '2019-01-01',
            'end_date' => '2023-01-01',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('education_entries', ['title' => 'B.S. in Computer Science']);
    }

    public function test_an_education_entry_end_date_cannot_precede_its_start(): void
    {
        $this->from('/admin/education/create')
            ->post('/admin/education', [
                'title' => 'B.S. in Computer Science',
                'start_date' => '2023-01-01',
                'end_date' => '2019-01-01',
            ])
            ->assertSessionHasErrors('end_date');
    }

    public function test_deleting_an_education_entry_removes_it(): void
    {
        $entry = EducationEntry::factory()->create();

        $this->delete("/admin/education/{$entry->id}");

        $this->assertDatabaseMissing('education_entries', ['id' => $entry->id]);
    }
}
