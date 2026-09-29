<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Guards on the two pieces of the design system that are expressed in the
 * stylesheet and the motion script rather than in PHP: the scrolling marquee and
 * the accent hover.
 *
 * Both have broken silently before — the marquee used to hand itself to a script
 * that could stop, and the hover has to stay a colour change so nothing reflows —
 * so the guarantees are asserted against the real source files.
 */
class MotionDesignTest extends TestCase
{
    use RefreshDatabase;

    private function css(): string
    {
        return (string) file_get_contents(base_path('resources/css/app.css'));
    }

    private function motion(): string
    {
        return (string) file_get_contents(base_path('resources/js/motion.js'));
    }

    /* -------------------------------------------------------------- marquee */

    public function test_the_marquee_is_animated_by_the_stylesheet_alone(): void
    {
        $css = $this->css();

        $this->assertMatchesRegularExpression(
            '/\.marquee__track\s*\{[^}]*animation:\s*marquee\s+[\d.]+s\s+linear\s+infinite/s',
            $css,
            'The strip should run a linear, infinite CSS animation.',
        );

        $this->assertStringNotContainsString(
            'marquee--js',
            $css,
            'No rule may switch the animation off once a script is present.',
        );
    }

    public function test_the_marquee_travels_one_group_on_a_gpu_transform(): void
    {
        $css = $this->css();

        $this->assertMatchesRegularExpression(
            '/@keyframes marquee\s*\{.*?from\s*\{\s*transform:\s*translate3d\(0,\s*0,\s*0\)/s',
            $css,
        );

        $this->assertMatchesRegularExpression(
            '/@keyframes marquee\s*\{.*?to\s*\{\s*transform:\s*translate3d\(calc\(-1 \* var\(--marquee-shift,\s*50%\)\),\s*0,\s*0\)/s',
            $css,
            'A loop has to travel exactly one group, or the wrap point shows a jump.',
        );
    }

    public function test_the_marquee_loop_length_defaults_to_the_two_rendered_groups(): void
    {
        // 50% of a track holding two identical groups is one group, which is the
        // width the animation has to travel for the loop to be invisible.
        $this->assertMatchesRegularExpression(
            '/--marquee-shift,\s*50%/',
            $this->css(),
            'Without JavaScript the fallback should still loop seamlessly.',
        );
    }

    public function test_the_marquee_is_clipped_so_it_cannot_scroll_the_page(): void
    {
        $this->assertMatchesRegularExpression(
            '/\.marquee\s*\{[^}]*overflow:\s*hidden/s',
            $this->css(),
        );
    }

    /**
     * The strip used to carry a hover pause, which froze it whenever the cursor
     * came to rest on it — the band sits directly under the hero, so that was
     * most visits. Nothing may pause it now.
     */
    public function test_nothing_can_pause_the_strip(): void
    {
        $this->assertStringNotContainsString(
            'animation-play-state',
            $this->css(),
            'A paused strip reads as broken rather than as an intentional pause.',
        );
    }

    /**
     * A decorative band whose whole purpose is to move keeps moving even where
     * the rest of the page stands still, and it stays clipped so it never turns
     * into something the visitor has to drag.
     */
    public function test_the_strip_keeps_moving_under_reduced_motion(): void
    {
        $css = $this->css();

        $reduced = strpos($css, '@media (prefers-reduced-motion: reduce)');
        $this->assertNotFalse($reduced, 'There should be a reduced-motion block.');

        $block = substr($css, $reduced);

        $this->assertMatchesRegularExpression(
            '/\.marquee__track\s*\{\s*animation:\s*marquee\s+[\d.]+s\s+linear\s+infinite\s*!important/s',
            $block,
            'The strip has to opt back in, and with !important to beat the blanket rule.',
        );

        $this->assertStringNotContainsString(
            'overflow-x: auto',
            $block,
            'The strip should be clipped, not handed to the visitor as a scrollbar.',
        );
    }

    public function test_the_marquee_renders_two_identical_groups_server_side(): void
    {
        $markup = (string) file_get_contents(base_path('resources/views/partials/marquee.blade.php'));

        $this->assertMatchesRegularExpression(
            '/for\s*\(\s*\$copy\s*=\s*0\s*;\s*\$copy\s*<\s*2/',
            $markup,
            'Two copies have to be in the HTML so the strip loops with no JavaScript.',
        );
    }

    public function test_the_script_only_measured_the_strip_and_never_animated_it(): void
    {
        $motion = $this->motion();

        $startup = strpos($motion, 'function initMarquee()');
        $next = strpos($motion, '/* ------', $startup);
        $body = substr($motion, $startup, $next - $startup);

        $this->assertStringNotContainsString(
            'requestAnimationFrame(tick)',
            $body,
            'The strip must not be driven per frame.',
        );

        $this->assertStringContainsString(
            '--marquee-shift',
            $body,
            'The script measures one group so a wide screen can be filled.',
        );
    }

    /* ------------------------------------------------------------ accent hover */

    public function test_headings_warm_to_the_accent_on_hover(): void
    {
        $css = $this->css();

        $this->assertMatchesRegularExpression(
            '/@media \(hover: hover\)\s*\{\s*\.title:hover,.*?\{\s*color: var\(--accent\);/s',
            $css,
            'Section headings should warm to the accent, inside a pointer-only query.',
        );

        $this->assertStringContainsString(
            '.showcase-item__title:hover',
            $css,
            'Project headings are the most likely place to look for the effect.',
        );
    }

    public function test_the_accent_hover_is_confined_to_real_pointers(): void
    {
        // Without the guard a tap on a touch screen leaves the heading in its
        // hover colour until something else is tapped.
        $this->assertSame(
            substr_count($this->css(), '@media (hover: hover)'),
            2,
            'Every accent hover rule should sit inside a hover-capable pointer query.',
        );
    }

    public function test_the_accent_hover_uses_the_existing_accent_token(): void
    {
        $css = $this->css();

        // The hover has to land on the token the rest of the design already uses,
        // so the effect reads as part of the palette rather than a new colour.
        $this->assertMatchesRegularExpression(
            '/--accent:\s*#ff8200;/i',
            $css,
        );

        $this->assertStringNotContainsString(
            '--hover-yellow',
            $css,
            'The effect should reuse the brand accent rather than introduce a colour.',
        );
    }

    public function test_outlined_headings_warm_their_stroke_rather_than_their_fill(): void
    {
        // An outlined heading has a transparent fill, so a colour change alone
        // would be invisible on it.
        $this->assertMatchesRegularExpression(
            '/\.outline-text:hover\s*\{\s*-webkit-text-stroke-color: var\(--accent\);/s',
            $this->css(),
        );
    }

    public function test_the_navigation_link_hover_uses_the_accent(): void
    {
        $this->assertMatchesRegularExpression(
            '/\.nav-list a:hover,\s*\.nav-list a\.is-active\s*\{\s*color: var\(--accent\);/s',
            $this->css(),
        );
    }

    /* --------------------------------------------------------- slide reveals */

    public function test_slide_reveals_are_transform_only_so_nothing_reflows(): void
    {
        $css = $this->css();

        $this->assertMatchesRegularExpression(
            '/\.js \[data-slide\]\s*\{\s*opacity: 0;\s*transform: translate3d/s',
            $css,
        );

        $this->assertStringNotContainsString(
            '[data-slide] {
        margin',
            $css,
            'A margin or padding on the reveal would move the resting layout.',
        );
    }

    /**
     * A card sits one gutter inside the edge of the page, so a fixed sideways
     * travel would push it past the viewport on a narrow screen, where the
     * horizontal overflow guard clips it part-way through the animation.
     */
    public function test_the_sideways_travel_is_capped_by_the_page_gutter(): void
    {
        $css = $this->css();

        $this->assertMatchesRegularExpression(
            "/--slide-from-x: min\(\d+px, calc\(var\(--pad\) \* [\d.]+\)\);/",
            $css,
            'Sideways travel should be capped against the wrap padding.',
        );

        $this->assertStringContainsString(
            'padding-inline: var(--pad);',
            $css,
            'The gutter the cap is measured against has to be the one cards sit in.',
        );
    }

    public function test_slide_reveals_are_undone_under_reduced_motion(): void
    {
        $css = $this->css();

        $reduced = strpos($css, '@media (prefers-reduced-motion: reduce)');
        $this->assertNotFalse($reduced, 'There should be a reduced-motion block.');

        $block = substr($css, $reduced);

        $this->assertMatchesRegularExpression(
            '/\[data-slide\]\s*\{\s*opacity: 1;\s*transform: none;/s',
            $block,
            'Reduced-motion visitors must never be left with hidden content.',
        );
    }

    public function test_every_slide_reveal_is_observed_rather_than_timed(): void
    {
        $motion = $this->motion();

        $startup = strpos($motion, 'function initSlideReveals()');
        $next = strpos($motion, '/* ------', $startup);
        $body = substr($motion, $startup, $next - $startup);

        $this->assertStringContainsString('IntersectionObserver', $body);
        $this->assertStringContainsString('prefersReduced()', $body);
        $this->assertStringNotContainsString('setInterval', $body);
        $this->assertStringNotContainsString('setTimeout', $body);
    }

    public function test_a_revealed_block_is_left_alone_so_scrolling_back_up_does_not_replay_it(): void
    {
        $motion = $this->motion();

        $startup = strpos($motion, 'function initSlideReveals()');
        $next = strpos($motion, '/* ------', $startup);
        $body = substr($motion, $startup, $next - $startup);

        $this->assertStringContainsString(
            'observer.unobserve(entry.target)',
            $body,
            'An element that has arrived should stop being watched.',
        );
    }

    /* --------------------------------------------------------- media reveals */

    /**
     * The declaration block belonging to a selector, and nothing else.
     *
     * Written by hand so a rule that quietly moves onto a child, the way the
     * media wipe used to move onto the wrong element, shows up as a failure
     * rather than as a rule that still exists somewhere in the file.
     *
     * @return string
     */
    private function declarationsFor(string $selector)
    {
        $pattern = '/(?:^|\})\s*'.preg_quote($selector, '/').'\s*\{([^}]*)\}/m';

        preg_match_all($pattern, $this->css(), $matches);

        return implode("\n", $matches[1]);
    }

    /**
     * A clip-path counts towards an element's intersection rect, so clipping the
     * box that IntersectionObserver is watching leaves it with no area left to
     * report. The observer therefore never sees it, the class that would
     * un-clip it is never added, and the effect cannot undo itself: every
     * project image stays hidden, on every device, with no way back.
     *
     * This shipped that way and the whole portfolio looked empty on a phone.
     * The box is hidden with opacity instead, which intersection maths ignores.
     */
    public function test_the_observed_media_box_is_hidden_with_opacity_and_never_clipped(): void
    {
        $box = $this->declarationsFor('.js [data-media-reveal]');

        $this->assertNotSame('', $box, 'The observed box should still have a hidden state.');

        $this->assertMatchesRegularExpression(
            '/opacity:\s*0;/',
            $box,
            'The box should start hidden, so the reveal still has something to do.',
        );

        $this->assertStringNotContainsString(
            'clip-path',
            $box,
            'Clipping the observed box empties its intersection rect, so the observer can never reveal it.',
        );

        $this->assertMatchesRegularExpression(
            '/\.js \[data-media-reveal\]\.is-visible\s*\{\s*opacity: 1;/s',
            $this->css(),
            'Adding the class has to make the box visible again.',
        );
    }

    /**
     * The wipe is the design, so it stays — on the image, which nothing is
     * observing, rather than on the box, which is.
     */
    public function test_the_wipe_is_clipped_onto_the_image_rather_than_the_observed_box(): void
    {
        $css = $this->css();

        $this->assertMatchesRegularExpression(
            '/\.js \[data-media-reveal\]\s*>\s*img\s*\{\s*clip-path:\s*inset\(0 0 100% 0\)/s',
            $css,
            'The image should start wiped away from the bottom.',
        );

        $this->assertMatchesRegularExpression(
            '/\.js \[data-media-reveal\]\.is-visible\s*>\s*img\s*\{\s*clip-path:\s*inset\(0 0 0 0\)/s',
            $css,
            'Revealing the box should open the wipe on the image.',
        );
    }

    public function test_media_reveals_are_undone_under_reduced_motion(): void
    {
        $css = $this->css();

        $reduced = strpos($css, '@media (prefers-reduced-motion: reduce)');
        $this->assertNotFalse($reduced, 'There should be a reduced-motion block.');

        $block = substr($css, $reduced);

        $this->assertMatchesRegularExpression(
            '/\[data-media-reveal\]\s*\{\s*opacity: 1;/s',
            $block,
            'Reduced-motion visitors must never be left with hidden content.',
        );

        $this->assertMatchesRegularExpression(
            '/\[data-media-reveal\]\s*>\s*img\s*\{\s*clip-path: none;/s',
            $block,
            'The wipe lives on the image now, so that is what has to be undone.',
        );
    }

    /**
     * The observer is one callback deep, and the content it guards is the whole
     * portfolio. A single missed callback must not be able to leave a project
     * image hidden, so anything the visitor has already scrolled past is
     * revealed regardless of what the observer reports.
     */
    public function test_media_the_visitor_has_scrolled_past_is_revealed_whatever_the_observer_says(): void
    {
        $motion = $this->motion();

        $startup = strpos($motion, 'function initMediaReveals()');
        $next = strpos($motion, '/* ------', $startup);
        $body = substr($motion, $startup, $next - $startup);

        $this->assertStringContainsString('IntersectionObserver', $body);
        $this->assertStringContainsString('prefersReduced()', $body);

        $this->assertStringContainsString(
            "addEventListener('scroll', sweep",
            $body,
            'There should be a backstop that runs without the observer.',
        );

        $this->assertStringContainsString(
            'getBoundingClientRect().bottom >= 0',
            $body,
            'The backstop should only rescue media that is already above the viewport.',
        );
    }
}
