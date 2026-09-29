<?php

namespace Tests\Feature;

use App\Models\EducationEntry;
use App\Models\Experience;
use App\Models\Profile;
use App\Models\Project;
use App\Models\ProjectCategory;
use App\Models\ProjectImage;
use App\Models\Setting;
use App\Models\Skill;
use App\Models\SkillCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PublicPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_renders_the_profile_name_and_headline(): void
    {
        Profile::factory()->create([
            'full_name' => 'Ada Lovelace',
            'title' => 'Lead Front-End Engineer',
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Ada Lovelace')
            ->assertSee('Lead Front-End Engineer');
    }

    /**
     * The animation layer only ever hides content behind a class the inline head
     * script sets, so the stylesheet can never strand a visitor on a blank page.
     * These cover the hooks the motion module looks for.
     */
    public function test_the_page_marks_itself_as_scripted_before_the_first_paint(): void
    {
        Profile::factory()->create();

        $this->get('/')
            ->assertOk()
            ->assertSee("document.documentElement.classList.add('js')", escape: false);
    }

    public function test_the_opening_curtain_and_scroll_progress_are_present(): void
    {
        Profile::factory()->create();

        $content = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('data-preloader', $content);
        $this->assertStringContainsString('data-scroll-progress', $content);
    }

    public function test_the_headline_lines_are_wrapped_in_their_own_masks(): void
    {
        Profile::factory()->create([
            'short_intro' => '',
        ]);

        Setting::factory()->create(['key' => 'hero.title_line_1', 'value' => 'First Line']);
        Setting::factory()->create(['key' => 'hero.title_line_2', 'value' => 'Second Line']);

        $content = $this->get('/')->assertOk()->getContent();

        $this->assertSame(2, substr_count((string) $content, 'class="mask-line"'));
        $this->assertStringContainsString('First Line', $content);
        $this->assertStringContainsString('Second Line', $content);
    }

    public function test_heading_figures_are_rendered_with_their_real_value(): void
    {
        Profile::factory()->create(['years_experience' => 9]);

        $category = ProjectCategory::factory()->create();
        Project::factory()->for($category, 'category')->create();

        $content = $this->get('/')->assertOk()->getContent();

        // The count-up rewrites these on the way up, so the server has to render
        // the finished figure for the no-script and reduced-motion cases.
        $this->assertMatchesRegularExpression(
            '/data-count="9"[^>]*>9</',
            (string) $content,
        );
    }

    public function test_a_section_with_no_content_renders_no_count_figure(): void
    {
        Profile::factory()->create(['years_experience' => null]);

        $content = $this->get('/')->assertOk()->getContent();

        $this->assertStringNotContainsString('data-count', $content);
    }

    public function test_each_showcase_media_is_marked_for_the_reveal_effect(): void
    {
        Profile::factory()->create();

        $category = ProjectCategory::factory()->create();
        Project::factory()->for($category, 'category')->create();

        $this->get('/')
            ->assertOk()
            ->assertSee('data-media-reveal', escape: false);
    }

    public function test_home_page_renders_the_full_description_and_gallery_of_a_project(): void
    {
        Profile::factory()->create();

        $category = ProjectCategory::factory()->create();

        $project = Project::factory()->for($category, 'category')->create([
            'title' => 'Traffic Sign Analytics',
            'full_description' => "First paragraph of the write up.\n\nSecond paragraph of the write up.",
        ]);

        ProjectImage::factory()->for($project)->create(['alt' => 'Confusion matrix screenshot']);

        $this->get('/')
            ->assertOk()
            ->assertSee('Traffic Sign Analytics')
            ->assertSee('First paragraph of the write up.')
            ->assertSee('Second paragraph of the write up.')
            ->assertSee('Confusion matrix screenshot');
    }

    public function test_a_project_falls_back_to_the_short_description(): void
    {
        Profile::factory()->create();

        $category = ProjectCategory::factory()->create();

        Project::factory()->for($category, 'category')->create([
            'short_description' => 'A short summary stands in for the long one.',
            'full_description' => null,
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('A short summary stands in for the long one.');
    }

    public function test_a_draft_project_is_hidden(): void
    {
        Profile::factory()->create();

        $category = ProjectCategory::factory()->create();

        Project::factory()->for($category, 'category')->create(['title' => 'Shipped Build']);
        Project::factory()->for($category, 'category')->draft()->create(['title' => 'Secret Build']);

        $this->get('/')
            ->assertOk()
            ->assertSee('Shipped Build')
            ->assertDontSee('Secret Build');
    }

    public function test_an_unfeatured_project_still_appears_in_its_showcase_section(): void
    {
        Profile::factory()->create();

        $category = ProjectCategory::factory()->create();

        Project::factory()->for($category, 'category')->create(['title' => 'Quiet Build']);

        $this->get('/')
            ->assertOk()
            ->assertSee('Quiet Build');
    }

    public function test_each_category_becomes_its_own_showcase_section(): void
    {
        Profile::factory()->create();

        $design = ProjectCategory::factory()->create(['name' => 'UI/UX Design', 'sort_order' => 0]);
        $code = ProjectCategory::factory()->create(['name' => 'Development', 'sort_order' => 1]);

        Project::factory()->for($design, 'category')->create(['title' => 'Design System']);
        Project::factory()->for($code, 'category')->create(['title' => 'Inference Pipeline']);

        $content = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('id="work-'.$design->slug.'"', $content);
        $this->assertStringContainsString('id="work-'.$code->slug.'"', $content);
        $this->assertStringContainsString('UI/UX Design', $content);
        $this->assertStringContainsString('Development', $content);
    }

    public function test_a_category_with_no_published_projects_renders_no_section(): void
    {
        Profile::factory()->create();

        $empty = ProjectCategory::factory()->create(['name' => 'Empty Shelf']);
        $filled = ProjectCategory::factory()->create(['name' => 'Real Work']);

        Project::factory()->draft()->for($empty, 'category')->create(['title' => 'Hidden Work']);
        Project::factory()->for($filled, 'category')->create(['title' => 'Visible Work']);

        $content = $this->get('/')->assertOk()->getContent();

        $this->assertStringNotContainsString('id="work-'.$empty->slug.'"', $content);
        $this->assertStringContainsString('id="work-'.$filled->slug.'"', $content);
        $this->assertStringNotContainsString('Empty Shelf', $content);
    }

    public function test_a_project_with_no_category_is_not_rendered(): void
    {
        Profile::factory()->create();

        Project::factory()->create(['title' => 'Unfiled Build']);

        $this->get('/')
            ->assertOk()
            ->assertDontSee('Unfiled Build');
    }

    public function test_the_category_intro_is_rendered_as_the_section_lead(): void
    {
        Profile::factory()->create();

        $category = ProjectCategory::factory()->create([
            'name' => 'UI/UX Design',
            'description' => 'Interface work grounded in research.',
        ]);

        Project::factory()->for($category, 'category')->create();

        $this->get('/')
            ->assertOk()
            ->assertSee('Interface work grounded in research.');
    }

    /**
     * The nav and the page body are generated separately, so a section could
     * exist in one and not the other. Every nav anchor must resolve.
     */
    public function test_every_navigation_anchor_resolves_to_a_section_on_the_page(): void
    {
        Profile::factory()->create();

        Skill::factory()->forCategory(SkillCategory::factory()->create())->create();
        Experience::factory()->create();
        EducationEntry::factory()->create();

        $design = ProjectCategory::factory()->create(['name' => 'UI/UX Design']);
        $code = ProjectCategory::factory()->create(['name' => 'Development']);

        Project::factory()->for($design, 'category')->create();
        Project::factory()->for($code, 'category')->create();

        $content = $this->get('/')->assertOk()->getContent();

        preg_match_all('/data-nav-anchor="([^"]+)"/', $content, $matches);

        $this->assertNotEmpty($matches[1], 'The header rendered no navigation entries.');

        foreach (array_unique($matches[1]) as $anchor) {
            $this->assertStringContainsString(
                'id="'.$anchor.'"',
                $content,
                "Navigation anchor #{$anchor} has no matching section on the page.",
            );
        }
    }

    public function test_a_section_with_no_content_is_left_out_of_both_page_and_navigation(): void
    {
        Profile::factory()->create();

        $content = $this->get('/')->assertOk()->getContent();

        $this->assertStringNotContainsString('id="skills"', $content);
        $this->assertStringNotContainsString('data-nav-anchor="skills"', $content);
        $this->assertStringNotContainsString('id="experience"', $content);
        $this->assertStringNotContainsString('data-nav-anchor="experience"', $content);
    }

    public function test_navigation_anchors_stay_in_page_order(): void
    {
        Profile::factory()->create();

        Skill::factory()->forCategory(SkillCategory::factory()->create())->create();
        Experience::factory()->create();

        // A category only becomes a section once it has something to show, so
        // each one needs a published project to appear at all.
        $first = ProjectCategory::factory()->create(['name' => 'First Section', 'slug' => 'first-section', 'sort_order' => 0]);
        $second = ProjectCategory::factory()->create(['name' => 'Second Section', 'slug' => 'second-section', 'sort_order' => 1]);

        Project::factory()->for($first, 'category')->create();
        Project::factory()->for($second, 'category')->create();

        $content = $this->get('/')->assertOk()->getContent();

        preg_match_all('/data-nav-anchor="([^"]+)"/', $content, $matches);

        $anchors = array_values(array_unique($matches[1]));

        $this->assertSame(['about', 'work-first-section', 'work-second-section', 'skills', 'experience', 'contact'], $anchors);
    }

    public function test_navigation_labels_come_from_settings(): void
    {
        Profile::factory()->create();

        Setting::put('nav.about', 'Start');

        $this->get('/')
            ->assertOk()
            ->assertSee('Start');
    }

    public function test_a_category_name_becomes_the_navigation_label(): void
    {
        Profile::factory()->create();

        $category = ProjectCategory::factory()->create(['name' => 'Interface Craft']);

        Project::factory()->for($category, 'category')->create();

        $content = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString(
            'data-nav-anchor="work-'.$category->slug.'"',
            $content,
        );
        $this->assertStringContainsString('Interface Craft', $content);
    }

    /**
     * @return array<int, array<int, string>>
     */
    public static function retiredPages(): array
    {
        return [
            ['/about'],
            ['/experience'],
            ['/skills'],
            ['/work'],
            ['/projects/some-project'],
        ];
    }

    #[DataProvider('retiredPages')]
    public function test_the_site_is_a_single_page(string $path): void
    {
        Profile::factory()->create();

        $this->get($path)->assertNotFound();
    }

    public function test_there_is_no_contact_page_to_get(): void
    {
        Profile::factory()->create();

        // The form still posts to /contact; there is simply no page to serve.
        $this->get('/contact')->assertStatus(405);
    }

    public function test_resume_streams_the_uploaded_pdf_with_a_disposition(): void
    {
        Storage::fake('public');

        $profile = Profile::factory()->create(['cv_path' => 'portfolio/cv/resume.pdf']);

        Storage::disk('public')->put('portfolio/cv/resume.pdf', '%PDF-1.4 test');

        $response = $this->get('/resume');

        $response->assertOk();
        $this->assertStringContainsString('attachment', $response->headers->get('Content-Disposition'));
        $this->assertStringContainsString(
            str($profile->full_name)->slug()->append('.pdf')->value(),
            $response->headers->get('Content-Disposition'),
        );
    }

    public function test_resume_returns_404_when_no_cv_is_uploaded(): void
    {
        Profile::factory()->withoutCv()->create();

        $this->get('/resume')->assertNotFound();
    }

    public function test_resume_returns_404_when_the_stored_file_is_missing(): void
    {
        Storage::fake('public');

        Profile::factory()->create(['cv_path' => 'portfolio/cv/deleted.pdf']);

        $this->get('/resume')->assertNotFound();
    }

    public function test_resume_button_is_hidden_when_no_cv_is_uploaded(): void
    {
        Profile::factory()->withoutCv()->create();

        $this->get('/')
            ->assertOk()
            ->assertDontSee('Get My Resume');
    }

    public function test_resume_button_is_shown_when_a_cv_is_uploaded(): void
    {
        Profile::factory()->create(['cv_path' => 'portfolio/cv/resume.pdf']);

        $this->get('/')
            ->assertOk()
            ->assertSee('Get My Resume');
    }

    public function test_sitemap_lists_only_the_home_page(): void
    {
        Profile::factory()->withoutCv()->create();

        $response = $this->get('/sitemap.xml');

        $response->assertOk();

        preg_match_all('/<loc>([^<]+)<\/loc>/', (string) $response->getContent(), $matches);

        $this->assertSame([route('home')], $matches[1]);
    }

    public function test_sitemap_includes_the_resume_when_a_cv_is_uploaded(): void
    {
        Storage::fake('public');

        Profile::factory()->create(['cv_path' => 'portfolio/cv/resume.pdf']);

        Storage::disk('public')->put('portfolio/cv/resume.pdf', '%PDF-1.4 test');

        $response = $this->get('/sitemap.xml');

        $response->assertOk();
        $this->assertStringContainsString(route('resume'), (string) $response->getContent());
    }

    public function test_sitemap_uses_the_configured_application_url(): void
    {
        Profile::factory()->create();

        $response = $this->get('/sitemap.xml');

        $response->assertOk();
        $this->assertStringStartsWith(
            '<?xml version="1.0" encoding="UTF-8"?>',
            $response->getContent(),
        );
        $this->assertStringContainsString(config('app.url'), $response->getContent());
    }

    public function test_robots_txt_points_at_the_sitemap(): void
    {
        Profile::factory()->create();

        $response = $this->get('/robots.txt');

        $response->assertOk();
        $this->assertStringContainsString('Sitemap: '.config('app.url').'/sitemap.xml', $response->getContent());
    }

    public function test_profile_is_created_on_first_visit_when_no_row_exists(): void
    {
        $this->assertSame(0, Profile::query()->count());

        $this->get('/')->assertOk();

        $this->assertSame(1, Profile::query()->count());
        $this->assertSame('Your Name', Profile::current()->full_name);
    }

    public function test_the_home_page_has_exactly_one_h1(): void
    {
        Profile::factory()->create();

        $category = ProjectCategory::factory()->create();

        Project::factory()->for($category, 'category')->featured()->create();

        $content = $this->get('/')->assertOk()->getContent();

        $this->assertSame(1, substr_count((string) $content, '<h1'));
    }

    public function test_section_headings_are_h2_below_the_single_h1(): void
    {
        Profile::factory()->create();

        $content = $this->get('/')->assertOk()->getContent();

        $this->assertSame(1, substr_count((string) $content, '<h1'));

        // Hero, about and contact always render.
        $this->assertSame(3, substr_count((string) $content, '<h2'));
    }

    public function test_footer_headline_comes_from_settings(): void
    {
        Profile::factory()->create();

        Setting::put('footer.headline', 'Let us build something sharp');

        $this->get('/')
            ->assertOk()
            ->assertSee('Let us build something sharp');
    }
}
