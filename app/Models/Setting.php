<?php

namespace App\Models;

use Database\Factories\SettingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

#[Fillable(['key', 'value', 'group', 'type', 'label', 'sort_order'])]
class Setting extends Model
{
    /** @use HasFactory<SettingFactory> */
    use HasFactory;

    /**
     * The groups the settings screen renders, in order.
     *
     * @return array<string, string>
     */
    public static function groups(): array
    {
        return [
            'general' => 'General',
            'navigation' => 'Navigation',
            'hero' => 'Hero',
            'sections' => 'Section headings',
            'projects' => 'Showcase projects',
            'marquee' => 'Marquee',
            'contact' => 'Contact',
            'seo' => 'SEO',
        ];
    }

    /**
     * Read a single setting, falling back to a default when it is missing or blank.
     */
    public static function get(string $key, ?string $default = null): ?string
    {
        $settings = static::allValues();

        $value = $settings[$key] ?? null;

        return filled($value) ? $value : $default;
    }

    /**
     * Every setting as a key => value map.
     *
     * The cache holds a plain array rather than a Collection so it stays
     * readable and cannot break when the cached value is out of date.
     *
     * @return array<string, string|null>
     */
    public static function allValues(): array
    {
        return Cache::rememberForever(
            'portfolio.settings',
            fn (): array => static::query()->pluck('value', 'key')->all(),
        );
    }

    public static function put(string $key, ?string $value): void
    {
        static::query()->updateOrCreate(['key' => $key], ['value' => $value]);

        Cache::forget('portfolio.settings');
    }

    public static function flushCache(): void
    {
        Cache::forget('portfolio.settings');
    }
}
