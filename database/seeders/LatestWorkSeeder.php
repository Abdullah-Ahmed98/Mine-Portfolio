<?php

namespace Database\Seeders;

use App\Models\LatestWorkCategory;
use App\Models\LatestWorkItem;
use App\Services\CoverArtService;
use Illuminate\Database\Seeder;

/**
 * Fills the "Latest work" strip so the section can be seen and judged with real
 * content in it.
 *
 * PLACEHOLDER COPY. Like the three undated showcase projects, these entries are
 * written to be plausible for the skill set and claim no clients. They exist so
 * the section is visible while it is being looked at, and every one of them
 * should be replaced with real work from the admin. Running
 * `php artisan latest-work:clear` empties the section again without touching
 * anything else.
 */
class LatestWorkSeeder extends Seeder
{
    public function run(): void
    {
        LatestWorkItem::query()->delete();
        LatestWorkCategory::query()->delete();

        $covers = app(CoverArtService::class);

        $categories = [
            [
                'name' => 'Interface Design',
                'description' => 'Screens, systems and the front-end builds that carry them.',
                'sort_order' => 0,
            ],
            [
                'name' => 'Development',
                'description' => 'Platforms, models and the tooling around them.',
                'sort_order' => 1,
            ],
        ];

        $categoryIds = [];

        foreach ($categories as $category) {
            $categoryIds[$category['name']] = LatestWorkCategory::create([
                ...$category,
                'slug' => LatestWorkCategory::slugFor($category['name']),
            ])->id;
        }

        $items = [
            [
                'title' => 'Realtime Analytics Workspace',
                'category' => 'Interface Design',
                'short_description' => 'A dense reporting surface rebuilt so the numbers people check every morning are readable at a glance.',
                'details' => "Six charts that used to sit in separate tabs now share one filter bar, and the loading states were designed rather than defaulted.\n\nThe rebuild cut the path from landing to first answer from nine clicks to two, and the layout holds from a phone up to a wall display.",
                'project_url' => 'https://example.test/analytics',
                'technologies' => ['Figma', 'Webflow', 'Design Systems'],
                'is_featured' => true,
                'is_visible' => true,
            ],
            [
                'title' => 'Design System & Component Library',
                'category' => 'Interface Design',
                'short_description' => 'One source of truth for type, spacing, colour and components, so marketing pages ship in days rather than weeks.',
                'details' => 'A single library rebuilt as production components, with documented usage rules so a new page can be built without waiting on a design review.',
                'project_url' => 'https://example.test/design-system',
                'technologies' => ['Figma', 'Webflow', 'Design Systems'],
                'is_featured' => false,
                'is_visible' => true,
            ],
            [
                'title' => 'Publishing Platform & CMS',
                'category' => 'Development',
                'short_description' => 'A Laravel application where editors manage everything on the page without touching code.',
                'details' => "Content is modelled rather than hardcoded, so a section, a project or a credit can be added, reordered or hidden from the admin and appear on the site straight away.\n\nImages are re-encoded on upload and stored with responsive widths, which keeps the page weight flat as the library grows.",
                'project_url' => 'https://example.test/publishing',
                'technologies' => ['Laravel', 'Blade', 'MySQL', 'Tailwind CSS'],
                'is_featured' => true,
                'is_visible' => true,
            ],
            [
                'title' => 'Predictive Maintenance Prototype',
                'category' => 'Development',
                'short_description' => 'Sensor time series scored ahead of failure, with an interface that shows why a machine was flagged.',
                'details' => "A model trained on vibration and temperature history, wrapped in a view that ranks machines by urgency instead of by score.\n\nThe point was legibility: an engineer should be able to agree or disagree with a flag without reading the maths.",
                'project_url' => null,
                'technologies' => ['Python', 'scikit-learn', 'Pandas'],
                'is_featured' => false,
                'is_visible' => true,
            ],
            [
                'title' => 'Field Survey App',
                'category' => 'Development',
                'short_description' => 'Offline-first data collection for surveyors working where there is no signal.',
                'details' => 'Collected records queue locally and reconcile on reconnect, with conflict resolution that keeps the original entry rather than silently overwriting it.',
                'project_url' => 'https://example.test/field-survey',
                'technologies' => ['Laravel', 'SQLite', 'PWA'],
                'is_featured' => false,
                'is_visible' => true,
            ],
            [
                'title' => 'Archive of Earlier Work',
                'category' => 'Interface Design',
                'short_description' => 'Kept visible for now, to show how a hidden entry behaves on the page.',
                'details' => 'This one is switched off in the admin. It is here to demonstrate that disabling an item removes it from the strip without deleting it.',
                'project_url' => 'https://example.test/archive',
                'technologies' => ['Figma'],
                'is_featured' => false,
                'is_visible' => false,
            ],
        ];

        foreach ($items as $position => $item) {
            $title = $item['title'];

            LatestWorkItem::create([
                'title' => $title,
                'slug' => LatestWorkItem::slugFor($title),
                'latest_work_category_id' => $categoryIds[$item['category']],
                'short_description' => $item['short_description'],
                'details' => $item['details'],
                'image' => $covers->generate($title, 'portfolio/latest-work'),
                'project_url' => $item['project_url'],
                'technologies' => $item['technologies'],
                'is_featured' => $item['is_featured'],
                'is_visible' => $item['is_visible'],
                'sort_order' => $position,
            ]);
        }
    }
}
