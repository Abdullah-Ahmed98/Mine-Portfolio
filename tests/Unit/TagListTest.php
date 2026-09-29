<?php

namespace Tests\Unit;

use App\Support\TagList;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class TagListTest extends TestCase
{
    /**
     * @return array<string, array{0: mixed, 1: array<int, string>}>
     */
    public static function inputs(): array
    {
        return [
            'null' => [null, []],
            'empty string' => ['', []],
            'whitespace only' => ["  \n \t ", []],
            'single tag' => ['Blade', ['Blade']],
            'comma separated' => ['Blade, Alpine, Vite', ['Blade', 'Alpine', 'Vite']],
            'newline separated' => ["Blade\nAlpine\nVite", ['Blade', 'Alpine', 'Vite']],
            'mixed separators' => ["Blade, Alpine\nVite", ['Blade', 'Alpine', 'Vite']],
            'trims each tag' => ['  Blade  ,   Alpine  ', ['Blade', 'Alpine']],
            'drops empty items' => ['Blade,,,Alpine', ['Blade', 'Alpine']],
            'drops blank lines' => ["Blade\n\n\nAlpine", ['Blade', 'Alpine']],
            'removes duplicates' => ['Blade, Alpine, Blade', ['Blade', 'Alpine']],
            'keeps the first occurrence order' => ['Vite, Blade, Alpine', ['Vite', 'Blade', 'Alpine']],
            'duplicates are case sensitive' => ['Blade, blade', ['Blade', 'blade']],
            'accepts an array' => [['Blade', ' Alpine '], ['Blade', 'Alpine']],
            'de-duplicates an array' => [['Blade', 'Blade'], ['Blade']],
            'drops blanks from an array' => [['Blade', '', '  '], ['Blade']],
            'numeric array keys are reset' => [[5 => 'Blade', 9 => 'Alpine'], ['Blade', 'Alpine']],
        ];
    }

    #[DataProvider('inputs')]
    public function test_it_normalises_tag_input(mixed $value, array $expected): void
    {
        $this->assertSame($expected, TagList::parse($value));
    }

    public function test_it_renders_tags_back_to_text(): void
    {
        $this->assertSame('Blade, Alpine, Vite', TagList::toString(['Blade', 'Alpine', 'Vite']));
        $this->assertSame('', TagList::toString([]));
    }

    public function test_parsing_and_rendering_round_trips(): void
    {
        // Round tripping normalises the separators, which is the point: the
        // admin can type either form and always get the same stored list back.
        $this->assertSame(
            'Blade, Alpine, Vite',
            TagList::toString(TagList::parse("Blade, Alpine\nVite")),
        );
    }
}
