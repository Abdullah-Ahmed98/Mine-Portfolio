<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectImage;
use App\Services\MediaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProjectImageController extends Controller
{
    public function store(Request $request, Project $project, MediaService $media): RedirectResponse
    {
        $validated = $request->validate([
            'images' => ['required', 'array', 'min:1', 'max:10'],
            'images.*' => ['required', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:8192'],
            'alt' => ['nullable', 'string', 'max:180'],
        ]);

        $alt = $validated['alt'] ?? null;
        $order = ProjectImage::nextSortOrder($project->images());

        foreach ($validated['images'] as $file) {
            $path = $media->store($file, 'portfolio/projects');

            $project->images()->create([
                'path' => $path,
                'alt' => $alt,
                'is_cover' => false,
                'sort_order' => $order++,
            ]);
        }

        return back()->with('status', 'Image uploaded.');
    }

    public function update(Request $request, ProjectImage $image): RedirectResponse
    {
        $validated = $request->validate([
            'alt' => ['nullable', 'string', 'max:180'],
            'is_cover' => ['nullable', 'boolean'],
        ]);

        $isCover = $request->boolean('is_cover');

        if ($isCover && ! $image->is_cover) {
            $image->project->images()->update(['is_cover' => false]);
        }

        $image->update([
            'alt' => $validated['alt'] ?? null,
            'is_cover' => $isCover,
        ]);

        return back()->with('status', 'Image updated.');
    }

    public function destroy(ProjectImage $image, MediaService $media): RedirectResponse
    {
        $media->delete($image->path);

        $image->delete();

        return back()->with('status', 'Image removed.');
    }
}
