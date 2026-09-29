<?php

namespace App\Support;

use App\Models\ProjectCategory;
use Illuminate\Support\Collection;

/**
 * The single-page site is one document with anchor links, so the order of its
 * sections and the order of the navigation have to agree. Keeping the fixed
 * sections in one place stops the header and the body from drifting apart; a
 * feature test asserts every navigation anchor resolves to a section on the
 * page.
 */
final class SinglePage
{
    /**
     * Fixed sections in the order they appear down the page.
     *
     * `anchor` is both the element id and the hash used by the nav. `nav` marks
     * the sections that get a navigation entry. The project categories are not
     * listed here: they are dynamic, and slot in after latest work.
     */
    public const SECTIONS = [
        ['anchor' => 'about', 'nav' => true, 'label' => 'nav.about', 'fallback' => 'About'],
        ['anchor' => 'latest-work', 'nav' => true, 'label' => 'nav.latest_work', 'fallback' => 'Latest Work'],
        ['anchor' => 'showcase', 'nav' => false, 'label' => null, 'fallback' => null],
        ['anchor' => 'skills', 'nav' => true, 'label' => 'nav.skills', 'fallback' => 'Skills'],
        ['anchor' => 'experience', 'nav' => true, 'label' => 'nav.experience', 'fallback' => 'Experience'],
        ['anchor' => 'education', 'nav' => false, 'label' => 'nav.education', 'fallback' => 'Education'],
        ['anchor' => 'contact', 'nav' => true, 'label' => 'nav.contact', 'fallback' => 'Contact'],
    ];

    /**
     * The navigation entries, with the showcase categories spliced in where the
     * showcase block sits so the nav order matches the page order.
     *
     * Sections whose data is empty are omitted, because the page skips them too
     * and a link to a missing anchor would dead-end. `present` reports which
     * conditional sections have content; a section missing from the array is
     * assumed to always render.
     *
     * @param  Collection<int, ProjectCategory>  $categories
     * @param  array<string, bool>  $present
     * @return array<int, array{anchor: string, label: string}>
     */
    public static function navigation(Collection $categories, array $present = []): array
    {
        $items = [];

        foreach (self::SECTIONS as $section) {
            $anchor = $section['anchor'];

            if ($anchor === 'showcase') {
                foreach ($categories as $category) {
                    $items[] = [
                        'anchor' => 'work-'.$category->slug,
                        'label' => $category->name,
                    ];
                }

                continue;
            }

            if (! $section['nav']) {
                continue;
            }

            if (array_key_exists($anchor, $present) && ! $present[$anchor]) {
                continue;
            }

            $items[] = [
                'anchor' => $anchor,
                'label' => (string) setting($section['label'], $section['fallback']),
            ];
        }

        return $items;
    }
}
