<?php

namespace App\Support;

/**
 * Splits a block of long-form text into paragraphs.
 *
 * Long text is stored in a single column, and a blank line is what starts a new
 * paragraph. Every long-text field in the CMS follows that one convention, so
 * this is the only place that decides how such text is read back.
 */
class Paragraphs
{
    /**
     * Blank lines separate paragraphs, so runs of them collapse to one break and
     * any blank lines the author left at the edges are dropped.
     *
     * @return array<int, string>
     */
    public static function split(?string $text): array
    {
        return collect(preg_split('/\R{2,}/', (string) $text))
            ->map(fn (string $paragraph) => trim($paragraph))
            ->filter()
            ->values()
            ->all();
    }
}
