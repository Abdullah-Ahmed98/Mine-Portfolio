<?php

namespace App\Support;

use App\Models\Setting;

/**
 * Builds the per-page metadata bag the layout turns into <title>, meta tags and
 * JSON-LD. Keeping it in one place means every page gets the same fallbacks.
 */
final class Seo
{
    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    public static function make(string $title, string $description, ?string $image = null, array $overrides = []): array
    {
        $siteName = Setting::get('seo.site_name') ?? 'Portfolio';

        return array_merge([
            'title' => $title,
            'description' => $description,
            'image' => $image,
            'type' => 'website',
            'site_name' => $siteName,
            'keywords' => Setting::get('seo.keywords'),
        ], $overrides);
    }

    /**
     * The default title, used for the home page and as a suffix elsewhere.
     */
    public static function defaultTitle(): string
    {
        return Setting::get('seo.title') ?? 'Portfolio';
    }

    public static function defaultDescription(): string
    {
        return Setting::get('seo.description') ?? '';
    }
}
