<?php

namespace Tests\Feature;

use App\Models\LatestWorkCategory;
use App\Models\LatestWorkImage;
use App\Models\LatestWorkItem;
use App\Models\Profile;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * The "Latest work" section and the admin screen behind it.
 *
 * The section is generated entirely from the database, so these cover the whole
 * path an item takes: created in the admin, rendered on the page, edited,
 * hidden, and deleted.
 */
class LatestWorkTest extends TestCase
{
    use RefreshDatabase;

    /* ------------------------------------------------------------ frontend */

    public function test_the_section_is_left_out_entirely_when_there_is_nothing_to_show(): void
    {
        Profile::factory()->create();

        $content = $this->get('/')->assertOk()->getContent();

        $this->assertStringNotContainsString('id="latest-work"', $content);
        $this->assertStringNotContainsString('data-nav-anchor="latest-work"', $content);
    }

    public function test_a_visible_item_reaches_the_page_with_its_copy(): void
    {
        Profile::factory()->create();

        $category = LatestWorkCategory::factory()->create(['name' => 'UI/UX Design']);

        LatestWorkItem::factory()
            ->for($category, 'category')
            ->create([
                'title' => 'Redesigned Analytics Dashboard',
                'short_description' => 'A calmer reporting experience for a busy ops team.',
                'project_url' => 'https://example.test/dashboard',
            ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Redesigned Analytics Dashboard')
            ->assertSee('A calmer reporting experience for a busy ops team.')
            ->assertSee('UI/UX Design')
            ->assertSee('https://example.test/dashboard');
    }

    /**
     * The admin exposes a technologies field, so an entry made there has to come
     * back out on the page. A field that validates and stores but never renders
     * is worse than one that was never offered.
     */
    public function test_technologies_entered_in_the_admin_reach_the_page(): void
    {
        Profile::factory()->create();

        LatestWorkItem::factory()->create([
            'title' => 'Tagged Work',
            'technologies' => ['Laravel', 'Blade', 'Vite'],
        ]);

        $content = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('latest-work-card__tags', $content);

        foreach (['Laravel', 'Blade', 'Vite'] as $technology) {
            $this->assertStringContainsString($technology, $content);
        }
    }

    /**
     * Nothing to show means no empty container left behind in the markup.
     */
    public function test_the_tag_row_is_left_out_when_there_are_no_technologies(): void
    {
        Profile::factory()->create();

        LatestWorkItem::factory()->create([
            'title' => 'Untagged Work',
            'technologies' => [],
        ]);

        $content = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('Untagged Work', $content);
        $this->assertStringNotContainsString('latest-work-card__tags', $content);
    }

    public function test_a_hidden_item_is_absent_from_the_page(): void
    {
        Profile::factory()->create();

        LatestWorkItem::factory()->create(['title' => 'Hidden Piece', 'is_visible' => false]);
        LatestWorkItem::factory()->create(['title' => 'Visible Piece']);

        $content = $this->get('/')->assertOk()->getContent();

        $this->assertStringNotContainsString('Hidden Piece', $content);
        $this->assertStringContainsString('Visible Piece', $content);
    }

    public function test_the_section_appears_in_the_navigation_only_once_it_renders(): void
    {
        Profile::factory()->create();

        $before = $this->get('/')->assertOk()->getContent();

        preg_match_all('/data-nav-anchor="([^"]+)"/', $before, $matches);

        $this->assertNotContains('latest-work', $matches[1]);

        LatestWorkItem::factory()->create();

        $after = $this->get('/')->assertOk()->getContent();

        preg_match_all('/data-nav-anchor="([^"]+)"/', $after, $matches);

        $this->assertContains('latest-work', array_unique($matches[1]));
        $this->assertStringContainsString('id="latest-work"', $after);
    }

    public function test_items_render_in_their_display_order(): void
    {
        Profile::factory()->create();

        LatestWorkItem::factory()->create(['title' => 'Third', 'sort_order' => 2]);
        LatestWorkItem::factory()->create(['title' => 'First', 'sort_order' => 0]);
        LatestWorkItem::factory()->create(['title' => 'Second', 'sort_order' => 1]);

        $content = $this->get('/')->assertOk()->getContent();

        $this->assertLessThan(
            strpos($content, 'Second'),
            strpos($content, 'First'),
            'Items should follow their sort order down the page.',
        );
    }

    public function test_the_section_carries_the_animation_hooks(): void
    {
        Profile::factory()->create();

        LatestWorkItem::factory()->create();

        $content = $this->get('/')->assertOk()->getContent();

        // The tile's image unmasks as it arrives, and the heading splits into
        // words, both from the same motion module the rest of the page uses.
        $this->assertStringContainsString('data-media-reveal', $content);
        $this->assertStringContainsString('data-reveal-words', $content);
    }

    /**
     * The heading is one oversized line: a solid first half and a second half
     * drawn as an outline. Both halves are editable, and neither is hardcoded.
     */
    public function test_the_heading_is_two_editable_halves_with_the_second_outlined(): void
    {
        Profile::factory()->create();

        LatestWorkItem::factory()->create();

        Setting::put('section.latest_work.title', 'Selected');
        Setting::put('section.latest_work.title_outline', 'pieces');

        $content = $this->get('/')->assertOk()->getContent();

        $this->assertMatchesRegularExpression(
            '/<h2 class="latest-work__heading">\s*Selected<span class="latest-work__outline">&nbsp;pieces<\/span>/',
            $content,
        );
    }

    /**
     * The tiles travel up past a heading that holds still, so the heading pins
     * and every other tile steps down. Both are layout, so they need no script.
     */
    public function test_the_heading_pins_and_alternating_tiles_step_down(): void
    {
        $css = (string) file_get_contents(resource_path('css/app.css'));

        $this->assertMatchesRegularExpression(
            '/@media \(min-width: 861px\)\s*\{\s*\.latest-work__heading\s*\{[^}]*position:\s*sticky/s',
            $css,
            'The heading has to hold still while the tiles travel past it.',
        );

        $this->assertMatchesRegularExpression(
            '/\.latest-work-card:nth-child\(even\)\s*\{\s*margin-top:/s',
            $css,
            'Every other tile steps down so a pair never sits shoulder to shoulder.',
        );

        // clip rather than hidden: hidden would make the section a scroll
        // container, which is precisely what stops a sticky child sticking.
        $this->assertMatchesRegularExpression(
            '/\.latest-work\s*\{[^}]*overflow-x:\s*clip/s',
            $css,
        );

        $this->assertStringNotContainsString(
            'latest-work-card__link',
            (string) file_get_contents(
                resource_path('views/partials/latest-work-card.blade.php')
            ),
            'A tile is either a link or a plain box, never a separate call to action.',
        );
    }

    /**
     * The section is full-bleed so the heading can use the whole viewport, but the
     * tiles still need a gutter of their own. Without it they touch both edges,
     * and on a 320px screen a single column has no margin left at all.
     *
     * The gutter has to be in the base rule rather than inside the desktop media
     * query, otherwise the narrow screens that need it most are the only ones
     * left without it.
     */
    public function test_the_tiles_keep_a_gutter_on_narrow_screens(): void
    {
        $css = (string) file_get_contents(resource_path('css/app.css'));

        $this->assertMatchesRegularExpression(
            '/\.latest-work__grid\s*\{[^}]*padding-inline:\s*\d+px/s',
            $css,
            'The grid needs a side gutter outside any media query, so mobile gets one too.',
        );

        // The gutter the sideways cap is measured against is the wrap padding,
        // so the two have to agree on what a gutter is.
        $this->assertMatchesRegularExpression(
            '/\.latest-work__grid\s*\{[^}]*padding-inline:\s*20px/s',
            $css,
            '20px is the narrow-screen gutter the rest of the site uses.',
        );
    }

    /**
     * The heading is one unbreakable run: the two halves are joined by a
     * non-breaking space, so it either fits or the section clips its ends off.
     *
     * Nothing else in the suite can catch that, because the markup and the
     * structure are both fine either way. It only shows up as a measurement, so
     * this reads the clamp back out of the stylesheet and checks the rendered
     * width against the space available, the way a browser would.
     */
    public function test_the_heading_fits_inside_its_gutter_on_every_screen(): void
    {
        $css = (string) file_get_contents(resource_path('css/app.css'));

        $this->assertMatchesRegularExpression(
            '/\.latest-work__heading\s*\{[^}]*padding-inline:\s*(\d+)px/s',
            $css,
            'The heading needs a side gutter outside any media query, like the tiles.',
        );

        $this->assertSame(
            1,
            preg_match('/\.latest-work__heading\s*\{[^}]*padding-inline:\s*(\d+)px/s', $css, $pad),
        );

        $gutter = (int) $pad[1];

        $this->assertSame(
            1,
            preg_match(
                '/\.latest-work__heading\s*\{[^}]*font-size:\s*clamp\(\s*(\d+)px\s*,\s*([\d.]+)vw\s*,\s*(\d+)px\s*\)/s',
                $css,
                $size
            ),
            'The heading font-size has to stay a clamp, or it cannot shrink on a small screen.',
        );

        $floor = (int) $size[1];
        $rate = (float) $size[2];
        $ceiling = (int) $size[3];

        $this->assertSame(
            1,
            preg_match('/--track-display:\s*(-?[\d.]+)em/', $css, $track),
            'The heading tracks with the site-wide display tracking.',
        );

        $tracking = (float) $track[1];

        /*
         * "Latest work" in Satoshi Bold 700, measured with imagettfbbox at a
         * 200px font-size: 1473px wide. That is 7.37em before letter-spacing,
         * and the em figure is what has to stay constant when the size changes.
         */
        $em = 7.37;
        $characters = mb_strlen("Latest\u{00A0}work");

        foreach ([280, 320, 360, 375, 390, 414, 430, 480, 600, 768, 861, 1024, 1280, 1440, 1920] as $viewport) {
            $fontSize = min(max($floor, $viewport * $rate / 100), $ceiling);

            // A 2px stroke is painted centred on the outline, so it grows the run.
            $rendered = ($em + $tracking * $characters) * $fontSize + 2;
            $available = $viewport - 2 * $gutter;

            $this->assertLessThanOrEqual(
                $available,
                $rendered,
                "The heading is cut off at {$viewport}px: it needs ".round($rendered).'px of the '
                    .$available.'px left inside the gutter.',
            );
        }
    }

    /**
     * The heading can only be clipped because it cannot wrap. If the two halves
     * were ever joined by an ordinary space the size rule above would stop being
     * load-bearing, and nobody would notice it had stopped working.
     */
    public function test_the_heading_is_a_single_unbreakable_run(): void
    {
        $this->assertStringContainsString(
            'latest-work__outline">&nbsp;',
            (string) file_get_contents(resource_path('views/partials/latest-work-section.blade.php')),
            'The heading halves are joined by a non-breaking space.',
        );
    }

    public function test_gallery_images_are_stacked_into_the_tile_for_the_hover_cycle(): void
    {
        Profile::factory()->create();

        $item = LatestWorkItem::factory()->create(['title' => 'Gallery Piece']);

        LatestWorkImage::factory()->create([
            'latest_work_item_id' => $item->id,
            'path' => 'portfolio/latest-work/one.jpg',
            'alt' => null,
        ]);
        LatestWorkImage::factory()->create([
            'latest_work_item_id' => $item->id,
            'path' => 'portfolio/latest-work/two.jpg',
            'alt' => 'A described shot',
        ]);

        $content = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('data-work-gallery', $content);
        $this->assertStringContainsString('latest-work-card__shot', $content);

        // Every shot has to be in the markup, or it could never be cycled to.
        $this->assertSame(
            2,
            substr_count($content, 'latest-work-card__shot'),
        );

        // Only the first is showing to begin with.
        $this->assertSame(
            1,
            substr_count($content, 'latest-work-card__shot is-current'),
        );

        // A shot with no alt of its own falls back to the item's title.
        $this->assertStringContainsString('alt="Gallery Piece"', $content);
        $this->assertStringContainsString('alt="A described shot"', $content);
    }

    /* ---------------------------------------------------------------- admin */

    public function test_the_admin_screens_require_authentication(): void
    {
        $this->get('/admin/latest-work')->assertRedirect(route('admin.login'));
        $this->get('/admin/latest-work/create')->assertRedirect(route('admin.login'));
        $this->get('/admin/latest-work-categories')->assertRedirect(route('admin.login'));
    }

    public function test_an_admin_can_create_an_item_and_it_reaches_the_page(): void
    {
        $category = LatestWorkCategory::factory()->create(['name' => 'Development']);

        $this->actingAs(User::factory()->create())
            ->post('/admin/latest-work', [
                'title' => 'Brand New Credit',
                'latest_work_category_id' => $category->id,
                'short_description' => 'Straight from the CMS.',
                'project_url' => 'https://example.test/credit',
                'technologies' => 'Laravel, Blade',
            ])
            ->assertRedirect(route('admin.latest-work.index'))
            ->assertSessionHasNoErrors();

        $item = LatestWorkItem::query()->sole();

        $this->assertSame('brand-new-credit', $item->slug);
        $this->assertSame(['Laravel', 'Blade'], $item->technologyList());
        $this->assertTrue($item->is_visible, 'A new item should be visible by default.');

        $this->get('/')->assertOk()->assertSee('Brand New Credit');
    }

    public function test_a_blank_slug_is_derived_from_the_title(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/admin/latest-work', ['title' => 'Traffic Sign Analytics'])
            ->assertSessionHasNoErrors();

        $this->assertSame('traffic-sign-analytics', LatestWorkItem::query()->sole()->slug);
    }

    public function test_a_slug_that_clashes_gets_a_suffix(): void
    {
        $this->actingAs(User::factory()->create());

        foreach ([1, 2] as $ignored) {
            $this->post('/admin/latest-work', ['title' => 'Duplicate Name'])->assertSessionHasNoErrors();
        }

        $this->assertSame(
            ['duplicate-name', 'duplicate-name-2'],
            LatestWorkItem::query()->orderBy('id')->pluck('slug')->all(),
        );
    }

    public function test_an_edit_reflects_on_the_page(): void
    {
        $item = LatestWorkItem::factory()->create(['title' => 'Before The Edit']);

        $this->actingAs(User::factory()->create())
            ->put("/admin/latest-work/{$item->slug}", ['title' => 'After The Edit'])
            ->assertRedirect(route('admin.latest-work.index'))
            ->assertSessionHasNoErrors();

        $content = $this->get('/')->assertOk()->getContent();

        $this->assertStringNotContainsString('Before The Edit', $content);
        $this->assertStringContainsString('After The Edit', $content);
    }

    public function test_disabling_an_item_removes_it_from_the_page_without_deleting_it(): void
    {
        $item = LatestWorkItem::factory()->create(['title' => 'Going Quiet']);

        $this->actingAs(User::factory()->create())
            ->put("/admin/latest-work/{$item->slug}", ['title' => 'Going Quiet', 'is_visible' => false])
            ->assertSessionHasNoErrors();

        $this->assertFalse($item->fresh()->is_visible);
        $this->assertDatabaseHas('latest_work_items', ['id' => $item->id]);

        $this->get('/')->assertOk()->assertDontSee('Going Quiet');
    }

    public function test_deleting_an_item_removes_it_from_the_page_and_deletes_its_images(): void
    {
        Storage::fake('public');

        $item = LatestWorkItem::factory()->create([
            'title' => 'Doomed Credit',
            'image' => 'portfolio/latest-work/cover.jpg',
        ]);

        Storage::disk('public')->put('portfolio/latest-work/cover.jpg', 'x');
        Storage::disk('public')->put('portfolio/latest-work/gallery.jpg', 'x');

        $image = LatestWorkImage::factory()->for($item, 'item')->create([
            'path' => 'portfolio/latest-work/gallery.jpg',
        ]);

        $this->actingAs(User::factory()->create())
            ->delete("/admin/latest-work/{$item->slug}")
            ->assertSessionHasNoErrors();

        $this->assertDatabaseMissing('latest_work_items', ['id' => $item->id]);
        $this->assertDatabaseMissing('latest_work_images', ['id' => $image->id]);

        Storage::disk('public')->assertMissing('portfolio/latest-work/cover.jpg');
        Storage::disk('public')->assertMissing('portfolio/latest-work/gallery.jpg');

        $this->get('/')->assertOk()->assertDontSee('Doomed Credit');
    }

    public function test_the_image_is_stored_replaced_and_removable(): void
    {
        Storage::fake('public');

        $this->actingAs(User::factory()->create())
            ->post('/admin/latest-work', [
                'title' => 'With An Image',
                'image' => UploadedFile::fake()->image('cover.jpg', 1200, 900),
            ])
            ->assertSessionHasNoErrors();

        $item = LatestWorkItem::query()->sole();
        $original = $item->image;

        $this->assertNotNull($original);
        Storage::disk('public')->assertExists($original);

        $this->put("/admin/latest-work/{$item->slug}", [
            'title' => 'With An Image',
            'image' => UploadedFile::fake()->image('new.jpg', 1200, 900),
        ])->assertSessionHasNoErrors();

        $replacement = $item->fresh()->image;

        $this->assertNotSame($original, $replacement);
        Storage::disk('public')->assertExists($replacement);
        Storage::disk('public')->assertMissing($original);

        $this->put("/admin/latest-work/{$item->slug}", [
            'title' => 'With An Image',
            'remove_image' => 1,
        ])->assertSessionHasNoErrors();

        $this->assertNull($item->fresh()->image);
        Storage::disk('public')->assertMissing($replacement);
    }

    public function test_gallery_images_can_be_added_reordered_and_deleted(): void
    {
        Storage::fake('public');

        $this->actingAs(User::factory()->create());

        $item = LatestWorkItem::factory()->create();

        $this->post("/admin/latest-work/{$item->slug}/images", [
            'images' => [
                UploadedFile::fake()->image('one.jpg', 800, 600),
                UploadedFile::fake()->image('two.jpg', 800, 600),
            ],
            'alt' => 'Shared caption',
        ])->assertSessionHasNoErrors();

        $this->assertCount(2, $item->images);

        $first = $item->images()->orderBy('sort_order')->first();

        $this->post('/admin/reorder/latest-work-images/'.$first->id, ['direction' => 'down'])
            ->assertSessionHasNoErrors();

        $this->assertSame(
            [$first->id + 1, $first->id],
            $item->images()->orderBy('sort_order')->pluck('id')->all(),
        );

        $this->delete("/admin/latest-work/images/{$first->id}")->assertSessionHasNoErrors();

        $this->assertCount(1, $item->images()->get());
    }

    public function test_the_item_requires_a_title(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/admin/latest-work', ['short_description' => 'No title here'])
            ->assertSessionHasErrors('title');

        $this->assertSame(0, LatestWorkItem::query()->count());
    }

    public function test_the_item_rejects_a_broken_url_and_a_missing_type(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/admin/latest-work', [
                'title' => 'Bad Input',
                'project_url' => 'not a url',
                'latest_work_category_id' => 9999,
            ])
            ->assertSessionHasErrors(['project_url', 'latest_work_category_id']);
    }

    public function test_an_item_can_be_moved_up_and_down(): void
    {
        $this->actingAs(User::factory()->create());

        $first = LatestWorkItem::factory()->create(['sort_order' => 0]);
        $second = LatestWorkItem::factory()->create(['sort_order' => 1]);

        $this->post('/admin/reorder/latest-work/'.$second->id, ['direction' => 'up'])
            ->assertSessionHasNoErrors();

        $this->assertSame(
            [$second->id, $first->id],
            LatestWorkItem::query()->ordered()->pluck('id')->all(),
        );
    }

    public function test_new_items_land_at_the_end_of_the_list(): void
    {
        $this->actingAs(User::factory()->create());

        foreach (['One', 'Two'] as $title) {
            $this->post('/admin/latest-work', ['title' => $title])->assertSessionHasNoErrors();
        }

        $this->assertSame(
            ['One', 'Two'],
            LatestWorkItem::query()->ordered()->pluck('title')->all(),
        );
    }

    /* --------------------------------------------------------------- types */

    public function test_a_type_can_be_created_and_used_by_an_item(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/admin/latest-work-categories', ['name' => 'Motion Design'])
            ->assertRedirect(route('admin.latest-work-categories.index'))
            ->assertSessionHasNoErrors();

        $category = LatestWorkCategory::query()->sole();

        $this->assertSame('motion-design', $category->slug);

        $this->post('/admin/latest-work', [
            'title' => 'Scrollytelling Piece',
            'latest_work_category_id' => $category->id,
        ])->assertSessionHasNoErrors();

        $this->assertSame($category->id, LatestWorkItem::query()->sole()->latest_work_category_id);
    }

    public function test_deleting_a_type_keeps_its_items(): void
    {
        $this->actingAs(User::factory()->create());

        $category = LatestWorkCategory::factory()->create();
        $item = LatestWorkItem::factory()->for($category, 'category')->create();

        $this->delete("/admin/latest-work-categories/{$category->id}")->assertSessionHasNoErrors();

        $this->assertDatabaseMissing('latest_work_categories', ['id' => $category->id]);
        $this->assertNull($item->fresh()->latest_work_category_id);
    }

    public function test_the_admin_index_shows_the_empty_state_when_there_is_nothing_yet(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/admin/latest-work')
            ->assertOk()
            ->assertSee('Nothing here yet');
    }
}
