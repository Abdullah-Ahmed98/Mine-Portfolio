<?php

namespace App\Support;

/**
 * Normalises the comma / newline separated tag input used by the admin panel
 * into a clean, de-duplicated list.
 */
class TagList
{
    /**
     * @return array<int, string>
     */
    public static function parse(mixed $value): array
    {
        if (is_array($value)) {
            $items = $value;
        } else {
            $items = preg_split('/[\r\n,]+/', (string) $value);
        }

        return collect($items)
            ->map(fn ($item) => trim((string) $item))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param  array<int, string>  $tags
     */
    public static function toString(array $tags): string
    {
        return implode(', ', $tags);
    }
}
