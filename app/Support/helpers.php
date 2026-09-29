<?php

use App\Models\Setting;
use App\Support\Paragraphs;

if (! function_exists('setting')) {
    /**
     * Read a CMS setting from a template.
     *
     * Kept as a short global helper so views stay readable and do not need a
     * `use` statement for every file, which would conflict with @extends
     * needing to be the first line in a template.
     */
    function setting(string $key, ?string $default = null): ?string
    {
        return Setting::get($key, $default);
    }
}

if (! function_exists('setting_paragraphs')) {
    /**
     * Read a CMS setting that holds more than one paragraph.
     *
     * Uses the same blank-line convention as every other long-text field in the
     * CMS, so the same words read the same way wherever they are entered.
     *
     * @return array<int, string>
     */
    function setting_paragraphs(string $key, ?string $default = null): array
    {
        return Paragraphs::split(Setting::get($key, $default));
    }
}
