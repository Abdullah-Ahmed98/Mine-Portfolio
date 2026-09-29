<?php

namespace Tests\Feature;

use App\Models\EducationEntry;
use App\Models\Experience;
use App\Models\LatestWorkCategory;
use App\Models\LatestWorkItem;
use App\Models\Profile;
use App\Models\ProfileHighlight;
use App\Models\Project;
use App\Models\ProjectCategory;
use App\Models\Skill;
use App\Models\SkillCategory;
use App\Models\SocialLink;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Every admin screen, checked the way a browser parses it.
 *
 * HTML does not allow one form inside another: a parser closes the outer form at
 * the first inner <form> it meets and carries on. A submit button that comes
 * after that point is left belonging to no form at all, so it renders and looks
 * perfectly normal but does nothing when clicked. Nothing in the response looks
 * wrong, which is what makes it so hard to spot from the outside.
 *
 * These tests fail on that, and on the ordinary version of the same mistake -
 * a button that names a form with form="id" where no such id exists.
 */
class AdminFormStructureTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Every admin URL that renders a form, paired with a factory so there is a
     * record to edit. Anything not listed here renders no form and has nothing
     * to check.
     *
     * @return array<string, array{0: string, 1: \Closure}>
     */
    public static function adminScreens(): array
    {
        return [
            'project create' => ['/admin/projects/create', fn () => null],
            'project edit' => ['/admin/projects/{slug}/edit', fn () => Project::factory()->create()],
            'project category create' => ['/admin/project-categories/create', fn () => null],
            'project category edit' => ['/admin/project-categories/{id}/edit', fn () => ProjectCategory::factory()->create()],
            'latest work create' => ['/admin/latest-work/create', fn () => null],
            'latest work edit' => ['/admin/latest-work/{slug}/edit', fn () => LatestWorkItem::factory()->create()],
            'latest work type create' => ['/admin/latest-work-categories/create', fn () => null],
            'latest work type edit' => ['/admin/latest-work-categories/{id}/edit', fn () => LatestWorkCategory::factory()->create()],
            'skill create' => ['/admin/skills/create', fn () => null],
            'skill edit' => ['/admin/skills/{id}/edit', fn () => Skill::factory()->create()],
            'skill category create' => ['/admin/skill-categories/create', fn () => null],
            'skill category edit' => ['/admin/skill-categories/{id}/edit', fn () => SkillCategory::factory()->create()],
            'experience create' => ['/admin/experiences/create', fn () => null],
            'experience edit' => ['/admin/experiences/{id}/edit', fn () => Experience::factory()->create()],
            'education create' => ['/admin/education/create', fn () => null],
            'education edit' => ['/admin/education/{id}/edit', fn () => EducationEntry::factory()->create()],
            'social link create' => ['/admin/social-links/create', fn () => null],
            'social link edit' => ['/admin/social-links/{id}/edit', fn () => SocialLink::factory()->create()],
            'highlight create' => ['/admin/highlights/create', fn () => null],
            'highlight edit' => ['/admin/highlights/{id}/edit', fn () => ProfileHighlight::factory()->create()],
            'profile' => ['/admin/profile', fn () => null],
            'settings' => ['/admin/settings', fn () => null],
        ];
    }

    /**
     * @param  \Closure(): (mixed)  $makeRecord
     */
    #[Test]
    #[DataProvider('adminScreens')]
    public function test_every_admin_form_is_structured_so_its_save_button_works(string $url, \Closure $makeRecord): void
    {
        Profile::factory()->create();

        $this->actingAs(User::factory()->create());

        $record = $makeRecord();

        $url = str_replace(
            ['{slug}', '{id}'],
            [(string) ($record?->slug ?? 1), (string) ($record?->id ?? 1)],
            $url
        );

        $html = $this->get($url)->assertOk()->getContent();

        $scan = $this->scanForms($html);

        $this->assertNotEmpty(
            $scan['forms'],
            "No form was rendered on $url, so there is nothing to submit."
        );

        // 1. No form may sit inside another. A parser closes the outer one at the
        //    first inner one, which is what strands the save button.
        //
        //    This has to be counted in the raw markup. A parsed document has
        //    already had the nesting resolved by the parser, so the evidence is
        //    gone by the time it can be inspected - which is the whole reason the
        //    fault is so easy to ship and so hard to notice.
        $this->assertLessThanOrEqual(
            1,
            $scan['deepest'],
            "A form is nested inside another on $url. The browser closes the outer one at the inner "
            .'<form>, so any save button below it belongs to no form and silently does nothing.'
        );

        // 2. Every submit button has to resolve to a form, either by sitting
        //    inside one or by naming one that exists.
        foreach ($scan['submits'] as $submit) {
            if ($submit['depth'] > 0) {
                continue;   // inside a form, which is the ordinary case
            }

            $this->assertNotSame(
                '',
                $submit['form'],
                "A submit button on $url is outside every form and names none, so clicking it does nothing."
            );

            $this->assertContains(
                $submit['form'],
                $scan['ids'],
                "A submit button on $url names form=\"{$submit['form']}\", but no form on the page has that id."
            );
        }
    }

    /**
     * The save button has to be the one wired up, and it has to be inside the
     * form it names rather than merely near it.
     */
    #[Test]
    public function test_the_project_save_button_survives_the_gallery_forms(): void
    {
        Profile::factory()->create();

        $this->actingAs(User::factory()->create());

        $project = Project::factory()->create();

        $content = $this->get("/admin/projects/{$project->slug}/edit")
            ->assertOk()
            ->getContent();

        // The save button is bound to the main form by id...
        $this->assertMatchesRegularExpression(
            '/<button[^>]*type="submit"[^>]*form="project-form"/',
            $content,
            'The save button has to name the main form, because the gallery upload needs a form of its own.'
        );

        // ...and that id is on the form that carries the fields and the method
        // override, so pressing save updates rather than creates a second row.
        $this->assertMatchesRegularExpression(
            '/<form[^>]*id="project-form"[^>]*>/',
            $content,
        );

        $this->assertSame(
            1,
            substr_count($content, 'id="project-form"'),
            'The id has to be unique, or the button would bind to the wrong form.'
        );
    }

    #[Test]
    public function test_the_latest_work_save_button_survives_the_gallery_forms(): void
    {
        Profile::factory()->create();

        $this->actingAs(User::factory()->create());

        $item = LatestWorkItem::factory()->create();

        $content = $this->get("/admin/latest-work/{$item->slug}/edit")
            ->assertOk()
            ->getContent();

        $this->assertMatchesRegularExpression(
            '/<button[^>]*type="submit"[^>]*form="latest-work-form"/',
            $content,
        );

        $this->assertMatchesRegularExpression(
            '/<form[^>]*id="latest-work-form"[^>]*>/',
            $content,
        );

        $this->assertSame(
            1,
            substr_count($content, 'id="latest-work-form"'),
        );
    }

    /**
     * A form that has been split in two still has to space its cards the way the
     * single form did, or fixing the buttons would quietly restyle the page.
     */
    #[Test]
    public function test_a_split_form_keeps_the_same_rhythm_as_the_grid_it_sits_in(): void
    {
        $admin = (string) file_get_contents(resource_path('css/admin.css'));

        $this->assertMatchesRegularExpression(
            '/\.form\s*\{[^}]*display:\s*grid;[^}]*gap:\s*16px/s',
            $admin,
        );

        $this->assertMatchesRegularExpression(
            '/\.form__group\s*\{[^}]*display:\s*grid;[^}]*gap:\s*16px/s',
            $admin,
            'A form that sits beside other content needs the same gap, or the page jumps when a card moves out of it.'
        );
    }

    /**
     * Walk the raw tag stream and record how deep each form sits and where each
     * submit button ended up.
     *
     * Deliberately not a parsed document: parsing resolves nested forms exactly
     * the way a browser does, which hides the fault these tests exist to catch.
     *
     * @return array{forms: int, deepest: int, ids: string[], submits: list<array{depth: int, form: string}>}
     */
    private function scanForms(string $html): array
    {
        // A button with no type attribute is a submit button, so every one is
        // collected and the type checked afterwards.
        preg_match_all(
            '~<form\b[^>]*>|</form\s*>|<button\b[^>]*>|<input\b[^>]*>~i',
            $html,
            $tags
        );

        $depth = 0;
        $deepest = 0;
        $forms = 0;
        $ids = [];
        $submits = [];

        foreach ($tags[0] as $tag) {
            if (stripos($tag, '<form') === 0) {
                $forms++;
                $depth++;
                $deepest = max($deepest, $depth);

                if (preg_match('~\bid="([^"]+)"~i', $tag, $id)) {
                    $ids[] = $id[1];
                }

                continue;
            }

            if (stripos($tag, '</form') === 0) {
                $depth = max(0, $depth - 1);

                continue;
            }

            $isSubmit = stripos($tag, '<button') === 0
                ? ! preg_match('~\btype="(?!submit)[^"]*"~i', $tag)
                : (bool) preg_match('~\btype="(?:submit|image)"~i', $tag);

            if (! $isSubmit) {
                continue;
            }

            $submits[] = [
                'depth' => $depth,
                'form' => preg_match('~\bform="([^"]+)"~i', $tag, $ref) ? $ref[1] : '',
            ];
        }

        return compact('forms', 'deepest', 'ids', 'submits');
    }
}
