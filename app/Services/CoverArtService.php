<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Produces original abstract cover art for projects that do not have a
 * screenshot uploaded yet, so the grid never shows a broken or empty card.
 *
 * The composition is derived from a seed string, which keeps a project's cover
 * stable across re-seeds while still looking different between projects.
 */
class CoverArtService
{
    private const WIDTH = 1600;

    private const HEIGHT = 1200;

    /**
     * Hue pairs (HSL base) blended into the dark background.
     *
     * @var array<int, array{0: float, 1: float}>
     */
    private const PALETTES = [
        [18, 32],
        [200, 220],
        [340, 360],
        [150, 175],
        [265, 290],
        [40, 55],
    ];

    /**
     * Render a cover for the given seed text and store it on the public disk.
     */
    public function generate(string $seed, string $directory = 'portfolio/covers'): string
    {
        $image = $this->render($seed);

        $path = trim($directory, '/').'/'.Str::slug($seed, '-').'.webp';

        ob_start();
        imagewebp($image, null, 74);
        $binary = (string) ob_get_clean();

        imagedestroy($image);

        Storage::disk('public')->put($path, $binary);

        return $path;
    }

    private function render(string $seed): \GdImage
    {
        $hash = crc32($seed);
        $palette = self::PALETTES[$hash % count(self::PALETTES)];

        $canvas = imagecreatetruecolor(self::WIDTH, self::HEIGHT);

        $this->paintBackground($canvas, $palette);
        $this->paintGlows($canvas, $hash, $palette);

        match ($hash % 6) {
            0 => $this->paintRings($canvas, $hash),
            1 => $this->paintDiagonalBands($canvas, $hash),
            2 => $this->paintDotMatrix($canvas, $hash),
            3 => $this->paintArcs($canvas, $hash),
            4 => $this->paintChevrons($canvas, $hash),
            default => $this->paintHalftoneBars($canvas, $hash),
        };

        $this->paintGrid($canvas);
        $this->paintVignette($canvas);
        $this->paintAccentStroke($canvas, $hash);

        return $canvas;
    }

    /**
     * Center opacity of the ambient glows, where 127 is fully transparent.
     */
    private const GLOW_ALPHA = 76;

    /**
     * @param  \GdImage  $canvas
     * @param  array{0: float, 1: float}  $palette
     */
    private function paintBackground($canvas, array $palette): void
    {
        [$from] = $palette;

        $top = $this->colorFromHsl($canvas, $from, 0.10, 0.055);
        $bottom = $this->colorFromHsl($canvas, $from, 0.12, 0.022);

        for ($y = 0; $y < self::HEIGHT; $y++) {
            $ratio = $y / self::HEIGHT;
            $line = $this->blend($top, $bottom, $ratio);

            imageline($canvas, 0, $y, self::WIDTH, $y, $line);
        }
    }

    /**
     * @param  \GdImage  $canvas
     * @param  array{0: float, 1: float}  $palette
     */
    private function paintGlows($canvas, int $hash, array $palette): void
    {
        $positions = [
            [(int) (self::WIDTH * 0.22), (int) (self::HEIGHT * 0.28)],
            [(int) (self::WIDTH * 0.78), (int) (self::HEIGHT * 0.72)],
        ];

        foreach ($positions as $index => [$cx, $cy]) {
            $hue = $palette[$index % 2];
            $radius = (int) (self::WIDTH * (0.20 + (($hash >> $index) % 10) / 100));

            $this->radialGlow($canvas, $cx, $cy, $radius, $this->colorFromHsl($canvas, $hue, 0.70, 0.48), self::GLOW_ALPHA);
        }
    }

    /**
     * @param  \GdImage  $canvas
     */
    private function paintRings($canvas, int $hash): void
    {
        [$cx, $cy] = $this->anchor($hash, 0, 0);
        imagealphablending($canvas, true);

        for ($i = 1; $i <= 9; $i++) {
            $radius = $i * 78;
            $color = $this->alphaColor($canvas, 255, 255, 255, 96 - $i * 8);

            imageellipse($canvas, $cx, $cy, $radius * 2, $radius * 2, $color);
        }
    }

    /**
     * @param  \GdImage  $canvas
     */
    private function paintDiagonalBands($canvas, int $hash): void
    {
        $offset = 120 + ($hash % 200);
        $step = 190;

        for ($i = 0; $i < 18; $i++) {
            $x = ($i - 6) * $step + $offset;
            $alpha = min(120, 118 - $i * 3);
            $color = $this->alphaColor($canvas, 255, 255, 255, $alpha);

            $points = [
                $x, 0,
                $x + 46, 0,
                $x + 46 - self::HEIGHT, self::HEIGHT,
                $x - self::HEIGHT, self::HEIGHT,
            ];

            imagefilledpolygon($canvas, $points, $color);
        }
    }

    /**
     * @param  \GdImage  $canvas
     */
    private function paintDotMatrix($canvas, int $hash): void
    {
        $cols = 26;
        $rows = 18;
        $gapX = self::WIDTH / $cols;
        $gapY = self::HEIGHT / $rows;

        for ($y = 0; $y < $rows; $y++) {
            for ($x = 0; $x < $cols; $x++) {
                $noise = (($x * 7) + ($y * 13) + $hash) % 11;
                $radius = 3 + ($noise > 6 ? 5 : 0);

                $color = $this->alphaColor($canvas, 255, 255, 255, 84 - $noise * 5);

                imagefilledellipse(
                    $canvas,
                    (int) ($x * $gapX + $gapX / 2),
                    (int) ($y * $gapY + $gapY / 2),
                    $radius * 2,
                    $radius * 2,
                    $color,
                );
            }
        }
    }

    /**
     * @param  \GdImage  $canvas
     */
    private function paintArcs($canvas, int $hash): void
    {
        [$cx, $cy] = $this->anchor($hash, 1, 0);
        imagealphablending($canvas, true);

        for ($i = 0; $i < 12; $i++) {
            $start = 180 + $i * 14;
            $end = $start + 96;
            $thickness = 3 + (($hash + $i) % 4);
            $color = $this->alphaColor($canvas, 255, 255, 255, 88 - $i * 5);

            imagesetthickness($canvas, $thickness);
            imagearc($canvas, $cx, $cy, 200 + $i * 96, 200 + $i * 96, $start, $end, $color);
        }

        imagesetthickness($canvas, 1);
    }

    /**
     * @param  \GdImage  $canvas
     */
    private function paintChevrons($canvas, int $hash): void
    {
        [$cx, $cy] = $this->anchor($hash, 0, 1);
        $thickness = 5 + ($hash % 3);

        imagesetthickness($canvas, $thickness);

        for ($i = 0; $i < 14; $i++) {
            $size = 60 + $i * 54;
            $color = $this->alphaColor($canvas, 255, 255, 255, 96 - $i * 6);

            imagearc($canvas, $cx, $cy, $size * 2, $size * 2, 210, 330, $color);
        }

        imagesetthickness($canvas, 1);
    }

    /**
     * @param  \GdImage  $canvas
     */
    private function paintHalftoneBars($canvas, int $hash): void
    {
        $columns = 22;
        $gap = self::WIDTH / $columns;

        for ($i = 0; $i < $columns; $i++) {
            $noise = (($i * 13) + $hash) % 17;
            $height = (int) (self::HEIGHT * (0.08 + $noise / 90));
            $x = (int) ($i * $gap + $gap * 0.36);
            $width = (int) ($gap * 0.28);
            $color = $this->alphaColor($canvas, 255, 255, 255, 114 - $noise * 2);

            imagefilledrectangle($canvas, $x, self::HEIGHT - $height, $x + $width, self::HEIGHT, $color);
        }
    }

    /**
     * Pick a focal point from a region of the frame, derived from the seed so
     * the same project always gets the same composition.
     *
     * @return array{0: int, 1: int}
     */
    private function anchor(int $hash, int $column, int $row): array
    {
        $quadrantX = ($hash >> (3 + $column * 2)) % 2;
        $quadrantY = ($hash >> (5 + $row * 2)) % 2;

        $x = [0.28, 0.72][$quadrantX];
        $y = [0.34, 0.68][$quadrantY];

        $jitterX = (($hash >> 9) % 14) / 100;
        $jitterY = (($hash >> 15) % 14) / 100;

        return [
            (int) (self::WIDTH * ($x + $jitterX)),
            (int) (self::HEIGHT * ($y + $jitterY)),
        ];
    }

    /**
     * @param  \GdImage  $canvas
     */
    private function paintGrid($canvas): void
    {
        $color = $this->alphaColor($canvas, 255, 255, 255, 108);

        for ($x = 0; $x <= self::WIDTH; $x += 80) {
            imageline($canvas, $x, 0, $x, self::HEIGHT, $color);
        }

        for ($y = 0; $y <= self::HEIGHT; $y += 80) {
            imageline($canvas, 0, $y, self::WIDTH, $y, $color);
        }
    }

    /**
     * Darkens the frame edges with four one-pixel gradient bands.
     *
     * @param  \GdImage  $canvas
     */
    private function paintVignette($canvas): void
    {
        $depth = (int) (self::HEIGHT * 0.20);

        for ($i = 0; $i < $depth; $i++) {
            $ratio = 1 - $i / $depth;
            $color = $this->alphaColor($canvas, 0, 0, 0, 118 - $ratio * 46);

            imageline($canvas, 0, $i, self::WIDTH, $i, $color);
            imageline($canvas, 0, self::HEIGHT - 1 - $i, self::WIDTH, self::HEIGHT - 1 - $i, $color);
            imageline($canvas, $i, 0, $i, self::HEIGHT, $color);
            imageline($canvas, self::WIDTH - 1 - $i, 0, self::WIDTH - 1 - $i, self::HEIGHT, $color);
        }
    }

    /**
     * @param  \GdImage  $canvas
     */
    private function paintAccentStroke($canvas, int $hash): void
    {
        $color = $this->alphaColor($canvas, 255, 130, 0, 62);
        $thickness = 6;

        imagesetthickness($canvas, $thickness);

        $y = (int) (self::HEIGHT * (0.24 + ($hash % 30) / 100));
        imageline($canvas, (int) (self::WIDTH * 0.08), $y, (int) (self::WIDTH * 0.30), $y, $color);

        imagesetthickness($canvas, 1);
    }

    /**
     * Radial falloff rendered on its own transparent layer and composited once,
     * so the nested steps cannot accumulate into a solid disc.
     *
     * @param  \GdImage  $canvas
     */
    private function radialGlow($canvas, int $cx, int $cy, int $radius, int $color, int $centerAlpha): void
    {
        [$red, $green, $blue] = $this->channels($color);
        $diameter = $radius * 2;

        $layer = imagecreatetruecolor($diameter, $diameter);
        imagealphablending($layer, false);
        imagesavealpha($layer, true);
        imagefilledrectangle($layer, 0, 0, $diameter, $diameter, imagecolorallocatealpha($layer, 0, 0, 0, 127));

        $steps = 40;

        for ($i = $steps; $i >= 0; $i--) {
            $ratio = $i / $steps;
            $size = (int) max(1, $radius * $ratio);
            $alpha = (int) round($centerAlpha + (127 - $centerAlpha) * $ratio);

            imagefilledellipse(
                $layer,
                $radius,
                $radius,
                $size * 2,
                $size * 2,
                imagecolorallocatealpha($layer, $red, $green, $blue, $alpha),
            );
        }

        imagealphablending($canvas, true);
        imagecopy($canvas, $layer, $cx - $radius, $cy - $radius, 0, 0, $diameter, $diameter);

        imagedestroy($layer);
    }

    /**
     * @param  \GdImage  $canvas
     */
    private function colorFromHsl($canvas, float $hue, float $saturation, float $lightness): int
    {
        $chroma = (1 - abs(2 * $lightness - 1)) * $saturation;
        $secondary = $chroma * (1 - abs(fmod($hue / 60, 2) - 1));
        $match = $lightness - $chroma / 2;

        [$r, $g, $b] = match (true) {
            $hue < 60 => [$chroma, $secondary, 0.0],
            $hue < 120 => [$secondary, $chroma, 0.0],
            $hue < 180 => [0.0, $chroma, $secondary],
            $hue < 240 => [0.0, $secondary, $chroma],
            $hue < 300 => [$secondary, 0.0, $chroma],
            default => [$chroma, 0.0, $secondary],
        };

        return imagecolorallocate(
            $canvas,
            (int) round(($r + $match) * 255),
            (int) round(($g + $match) * 255),
            (int) round(($b + $match) * 255),
        );
    }

    /**
     * @param  array<int, int>  $color
     */
    private function channels(int $color): array
    {
        return [($color >> 16) & 0xFF, ($color >> 8) & 0xFF, $color & 0xFF];
    }

    /**
     * GD wraps alpha values outside 0-127, which silently inverts transparency.
     *
     * @param  \GdImage  $canvas
     */
    private function alphaColor($canvas, int $red, int $green, int $blue, int|float $alpha): int
    {
        return imagecolorallocatealpha(
            $canvas,
            max(0, min(255, $red)),
            max(0, min(255, $green)),
            max(0, min(255, $blue)),
            max(0, min(127, (int) $alpha)),
        );
    }

    private function blend(int $from, int $to, float $ratio): int
    {
        $channels = [];

        foreach ([16, 8, 0] as $shift) {
            $a = ($from >> $shift) & 0xFF;
            $b = ($to >> $shift) & 0xFF;
            $channels[] = (int) round($a + ($b - $a) * $ratio);
        }

        return ($channels[0] << 16) | ($channels[1] << 8) | $channels[2];
    }
}
