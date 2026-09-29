<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LatestWorkImageRequest;
use App\Models\LatestWorkImage;
use App\Models\LatestWorkItem;
use App\Services\MediaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LatestWorkImageController extends Controller
{
    public function store(LatestWorkImageRequest $request, LatestWorkItem $latestWorkItem, MediaService $media): RedirectResponse
    {
        $validated = $request->validated();

        $alt = $validated['alt'] ?? null;
        $order = LatestWorkImage::nextSortOrder($latestWorkItem->images());

        foreach ($validated['images'] as $file) {
            $path = $media->store($file, 'portfolio/latest-work');

            $latestWorkItem->images()->create([
                'path' => $path,
                'alt' => $alt,
                'is_cover' => false,
                'sort_order' => $order++,
            ]);
        }

        return back()->with('status', 'Image uploaded.');
    }

    public function update(Request $request, LatestWorkImage $image): RedirectResponse
    {
        $validated = $request->validate([
            'alt' => ['nullable', 'string', 'max:180'],
            'is_cover' => ['nullable', 'boolean'],
        ]);

        $isCover = $request->boolean('is_cover');

        if ($isCover && ! $image->is_cover) {
            $image->item->images()->update(['is_cover' => false]);
        }

        $image->update([
            'alt' => $validated['alt'] ?? null,
            'is_cover' => $isCover,
        ]);

        return back()->with('status', 'Image updated.');
    }

    public function destroy(LatestWorkImage $image, MediaService $media): RedirectResponse
    {
        $media->delete($image->path);

        $image->delete();

        return back()->with('status', 'Image removed.');
    }
}
