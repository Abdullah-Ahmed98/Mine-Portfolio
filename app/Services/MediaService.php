<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Handles image uploads on top of plain GD: re-encodes uploads (which strips
 * EXIF and any embedded payload), then writes a small set of responsive widths.
 */
class MediaService
{
    /**
     * Widths generated for every upload, in addition to the master file.
     *
     * @var array<int, int>
     */
    public const WIDTHS = [480, 960, 1600];

    public const MAX_WIDTH = 2000;

    /**
     * Store an uploaded image and return the relative path of the master file.
     */
    public function store(UploadedFile $file, string $directory): string
    {
        $source = $this->readImage($file->getRealPath(), $file->getMimeType());

        $directory = trim($directory, '/');
        $name = Str::random(24);
        $extension = $this->preferredExtension($file->getMimeType());

        $path = "{$directory}/{$name}.{$extension}";

        $this->writeResized($source, $path, self::MAX_WIDTH);

        $sourceWidth = imagesx($source);
        $sourceHeight = imagesy($source);

        foreach (self::WIDTHS as $width) {
            if ($width >= $sourceWidth) {
                continue;
            }

            $this->writeResized($source, "{$directory}/{$name}@{$width}.webp", $width, 'webp');
        }

        imagedestroy($source);

        return $path;
    }

    /**
     * Build a srcset string from the generated variants of a stored image.
     */
    public function srcset(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        $disk = Storage::disk('public');
        $directory = dirname($path);
        $name = pathinfo($path, PATHINFO_FILENAME);
        $extension = pathinfo($path, PATHINFO_EXTENSION);

        $candidates = [];

        foreach (self::WIDTHS as $width) {
            $variant = "{$directory}/{$name}@{$width}.webp";

            if ($disk->exists($variant)) {
                $candidates[] = $disk->url($variant)." {$width}w";
            }
        }

        $candidates[] = $disk->url($path);

        return implode(', ', $candidates);
    }

    /**
     * Remove a master image together with every generated variant.
     */
    public function delete(?string $path): void
    {
        if (blank($path)) {
            return;
        }

        $disk = Storage::disk('public');
        $directory = dirname($path);
        $name = pathinfo($path, PATHINFO_FILENAME);

        $disk->delete($path);

        foreach (self::WIDTHS as $width) {
            $disk->delete("{$directory}/{$name}@{$width}.webp");
        }
    }

    /**
     * Replace an existing image with a new upload, cleaning up the old files.
     */
    public function replace(?string $oldPath, UploadedFile $file, string $directory): string
    {
        $path = $this->store($file, $directory);

        $this->delete($oldPath);

        return $path;
    }

    /**
     * @return \GdImage
     */
    private function readImage(string $absolutePath, string $mimeType)
    {
        $image = match ($mimeType) {
            'image/jpeg' => @imagecreatefromjpeg($absolutePath),
            'image/png' => @imagecreatefrompng($absolutePath),
            'image/webp' => @imagecreatefromwebp($absolutePath),
            'image/gif' => @imagecreatefromgif($absolutePath),
            default => null,
        };

        if ($image === false || $image === null) {
            throw new RuntimeException('The uploaded file could not be read as an image.');
        }

        return $image;
    }

    /**
     * Write a proportionally scaled copy, never upscaling beyond the source.
     *
     * @param  \GdImage  $source
     */
    private function writeResized($source, string $path, int $targetWidth, ?string $format = null): void
    {
        $format ??= pathinfo($path, PATHINFO_EXTENSION) ?: 'jpg';

        $sourceWidth = imagesx($source);
        $sourceHeight = imagesy($source);

        $width = min($targetWidth, $sourceWidth);
        $height = max(1, (int) round($sourceHeight * ($width / $sourceWidth)));

        $canvas = imagecreatetruecolor($width, $height);

        if (in_array($format, ['jpg', 'jpeg'], true)) {
            imagefill($canvas, 0, 0, imagecolorallocate($canvas, 10, 10, 12));
        } else {
            imagealphablending($canvas, false);
            imagesavealpha($canvas, true);
            imagefill($canvas, 0, 0, imagecolorallocatealpha($canvas, 0, 0, 0, 127));
        }

        imagecopyresampled($canvas, $source, 0, 0, 0, 0, $width, $height, $sourceWidth, $sourceHeight);

        $disk = Storage::disk('public');
        $disk->put($path, $this->encode($canvas, $format));

        imagedestroy($canvas);
    }

    /**
     * @param  \GdImage  $canvas
     */
    private function encode($canvas, string $format): string
    {
        ob_start();

        match ($format) {
            'webp' => imagewebp($canvas, null, 82),
            'png' => imagepng($canvas, null, 6),
            'gif' => imagegif($canvas),
            default => imagejpeg($canvas, null, 85),
        };

        return (string) ob_get_clean();
    }

    private function preferredExtension(string $mimeType): string
    {
        return match ($mimeType) {
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
            default => 'jpg',
        };
    }
}
