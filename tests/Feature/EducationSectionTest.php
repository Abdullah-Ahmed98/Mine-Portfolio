<?php

namespace Tests\Feature;

use App\Models\EducationEntry;
use App\Models\Experience;
use App\Models\Profile;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The written introduction under the Foundations heading, and the heading
 * component it is rendered through.
 */
class EducationSectionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function test_the_written_introduction_renders_under_the_heading(): void
    {
        Profile::factory()->create();
        EducationEntry::factory()->create();

        Setting::put('section.education.tag', 'Education & Achievements');
        Setting::put('section.education.title', 'Foundations');
        Setting::put('section.education.body', "First paragraph.\n\nSecond paragraph.\n\nThird paragraph.");

        $content = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('First paragraph.', $content);
        $this->assertStringContainsString('Second paragraph.', $content);
        $this->assertStringContainsString('Third paragraph.', $content);

        // The heading still reads as a heading, and the copy follows it rather
        // than sitting somewhere else on the page.
        $this->assertLessThan(
            strpos($content, 'First paragraph.'),
            strpos($content, 'Foundations'),
            'The introduction has to come after the heading, not before it.'
        );

        $this->assertSame(
            1,
            substr_count($content, 'education-copy'),
            'The introduction should be rendered once, in one block.'
        );
    }

    /**
     * A blank line starts a paragraph. A single line break is a different thing:
     * it stays inside the paragraph, so a title and its institution can sit on
     * two lines under one label.
     */
    #[Test]
    public function test_a_blank_line_starts_a_paragraph_and_a_single_break_stays_inside_one(): void
    {
        Profile::factory()->create();
        EducationEntry::factory()->create();

        Setting::put('section.education.body', "One.\n\nTwo\nThree.\n\nFour.");

        $content = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('One.', $content);

        $this->assertMatchesRegularExpression(
            '/Two\.?<br\s*\/?>\s*Three\./',
            $content,
            'A single line break inside a paragraph has to survive as a line break.'
        );

        $this->assertStringContainsString('Four.', $content);
    }

    /**
     * The prose is set by an author in a textarea, so it is escaped on the way
     * out. A paragraph must never be able to inject markup.
     */
    #[Test]
    public function test_the_introduction_is_escaped(): void
    {
        Profile::factory()->create();
        EducationEntry::factory()->create();

        Setting::put('section.education.body', 'A <script>alert(1)</script> claim & <b>bold</b>.');

        $content = $this->get('/')->assertOk()->getContent();

        $this->assertStringNotContainsString('<script>alert(1)</script>', $content);
        $this->assertStringContainsString('&lt;script&gt;', $content);
        $this->assertStringContainsString('&amp;', $content);
    }

    /**
     * The section is built from two separately editable parts, so neither one
     * being present may take the other down with it.
     */
    #[Test]
    public function test_the_section_survives_when_the_introduction_is_emptied(): void
    {
        Profile::factory()->create();
        EducationEntry::factory()->create(['title' => 'B.S. in Computer Science']);

        Setting::put('section.education.title', 'Foundations');
        Setting::put('section.education.body', '');

        $content = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('id="education"', $content);
        $this->assertStringContainsString('B.S. in Computer Science', $content);
        $this->assertStringNotContainsString('education-copy', $content);
    }

    #[Test]
    public function test_the_section_survives_when_every_entry_is_removed(): void
    {
        Profile::factory()->create();

        Setting::put('section.education.title', 'Foundations');
        Setting::put('section.education.body', 'The written introduction is all there is.');

        $content = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('id="education"', $content);
        $this->assertStringContainsString('The written introduction is all there is.', $content);
        $this->assertStringNotContainsString('info-grid', $content);
    }

    #[Test]
    public function test_the_whole_section_disappears_when_there_is_nothing_in_it(): void
    {
        Profile::factory()->create();

        Setting::put('section.education.body', '');

        $this->get('/')->assertOk()->assertDontSee('id="education"', escape: false);
    }

    /**
     * The round trip that matters: an author types this in the CMS and it shows
     * up on the page, then changes it and the change shows up too.
     */
    #[Test]
    public function test_the_introduction_is_editable_from_the_settings_screen(): void
    {
        Profile::factory()->create();
        EducationEntry::factory()->create();

        $this->actingAs(User::factory()->create());

        Setting::put('section.education.body', 'Before the edit.');

        $this->get('/')->assertOk()->assertSee('Before the edit.');

        // Saving sends you back to the screen you saved from, so the session has
        // to survive the round trip rather than dumping you on the home page.
        $this->from(route('admin.settings.index'))->put(route('admin.settings.update'), [
            'settings' => [
                'section.education.body' => "After the edit.\n\nWith a second paragraph.",
            ],
        ])->assertRedirect(route('admin.settings.index'));

        Setting::flushCache();

        $content = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('After the edit.', $content);
        $this->assertStringContainsString('With a second paragraph.', $content);
        $this->assertStringNotContainsString('Before the edit.', $content);
    }

    /**
     * A textarea is right for a long passage and wrong for a one-line heading, so
     * the settings screen picks between them by looking at the key.
     */
    #[Test]
    public function test_the_settings_screen_offers_the_introduction_a_textarea(): void
    {
        $this->actingAs(User::factory()->create());

        Setting::query()->delete();
        Setting::put('section.education.body', 'Line one.');

        $content = $this->get(route('admin.settings.index'))->assertOk()->getContent();

        $this->assertMatchesRegularExpression(
            '/<textarea[^>]*name="settings\[section\.education\.body\]"[^>]*>/',
            $content,
            'A key holding a body has to be edited in a textarea, not a one-line input.'
        );
    }

    /**
     * A heading must only ever show the intro it was given.
     *
     * An included view inherits every variable in the caller's scope, so a $lead
     * assigned anywhere earlier in the page leaked into each heading below it and
     * the whole page repeated one sentence. The heading is a component now, which
     * only ever sees the props passed to it.
     */
    #[Test]
    public function test_a_heading_never_borrows_its_intro_from_elsewhere_on_the_page(): void
    {
        Profile::factory()->create([
            'short_intro' => 'A sentence that belongs to the hero alone.',
        ]);

        EducationEntry::factory()->create();

        // No section has an intro of its own, so no heading may show one.
        $content = $this->get('/')->assertOk()->getContent();

        $this->assertStringNotContainsString(
            'section-head__lead',
            $content,
            'A heading showed an intro nobody gave it.'
        );

        // The hero keeps its own, which is the point.
        $this->assertStringContainsString('A sentence that belongs to the hero alone.', $content);
        $this->assertMatchesRegularExpression(
            '/class="lead hero__lead"[^>]*>\s*A sentence that belongs to the hero alone\./',
            $content,
        );
    }

    #[Test]
    public function test_only_the_heading_given_an_intro_shows_one(): void
    {
        Profile::factory()->create();

        // The experience section only renders with an entry to show.
        Experience::factory()->create();

        Setting::put('section.experience.lead', 'Only the experience section has an intro.');

        $content = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('Only the experience section has an intro.', $content);

        $this->assertSame(
            1,
            substr_count($content, 'section-head__lead'),
            'Exactly the one heading that was given an intro should show one.'
        );
    }
}
