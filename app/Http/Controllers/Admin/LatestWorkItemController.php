<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LatestWorkItemRequest;
use App\Models\LatestWorkCategory;
use App\Models\LatestWorkItem;
use App\Services\MediaService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;

class LatestWorkItemController extends Controller
{
    public function index(): View
    {
        return view('admin.latest-work.index', [
            'items' => LatestWorkItem::query()
                ->with('category')
                ->withCount('images')
                ->ordered()
                ->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.latest-work.form', [
            'item' => new LatestWorkItem,
            'categories' => $this->categoryOptions(),
        ]);
    }

    public function store(LatestWorkItemRequest $request, MediaService $media): RedirectResponse
    {
        $validated = $request->validated();

        if ($request->hasFile('image')) {
            $validated['image'] = $media->store($request->file('image'), 'portfolio/latest-work');
        }

        $validated['slug'] = $this->resolveSlug($request, $validated['title'] ?? null);

        LatestWorkItem::create([
            ...$validated,
            'sort_order' => LatestWorkItem::nextSortOrder(),
        ]);

        return redirect()->route('admin.latest-work.index')->with('status', 'Latest work item added.');
    }

    public function edit(LatestWorkItem $latestWorkItem): View
    {
        $latestWorkItem->load('images');

        return view('admin.latest-work.form', [
            'item' => $latestWorkItem,
            'categories' => $this->categoryOptions(),
        ]);
    }

    public function update(LatestWorkItemRequest $request, LatestWorkItem $latestWorkItem, MediaService $media): RedirectResponse
    {
        $validated = $request->validated();

        if ($request->boolean('remove_image')) {
            $media->delete($latestWorkItem->image);
            $validated['image'] = null;
        } elseif ($request->hasFile('image')) {
            $validated['image'] = $media->replace(
                $latestWorkItem->image,
                $request->file('image'),
                'portfolio/latest-work',
            );
        }

        $validated['slug'] = $this->resolveSlug($request, $validated['title'] ?? null, $latestWorkItem);

        $latestWorkItem->update($validated);

        return redirect()->route('admin.latest-work.index')->with('status', 'Latest work item updated.');
    }

    public function destroy(LatestWorkItem $latestWorkItem, MediaService $media): RedirectResponse
    {
        $media->delete($latestWorkItem->image);

        foreach ($latestWorkItem->images as $image) {
            $media->delete($image->path);
        }

        $latestWorkItem->delete();

        return back()->with('status', 'Latest work item removed.');
    }

    /**
     * A blank slug field means "derive it from the title", which is what the
     * form's hint promises. An update that omits the field entirely keeps the
     * current slug, so renaming a title can never break an anchor that has
     * already been shared.
     */
    private function resolveSlug(LatestWorkItemRequest $request, ?string $title, ?LatestWorkItem $current = null): string
    {
        if ($current !== null && ! $request->has('slug')) {
            return $current->slug;
        }

        $requested = $request->input('slug');

        return $this->uniqueSlug($title, $requested, $current);
    }

    private function uniqueSlug(?string $title, ?string $requested, ?LatestWorkItem $current = null): string
    {
        $base = LatestWorkItem::slugFor(filled($requested) ? $requested : ($title ?? 'latest-work-item'));

        $slug = $base;
        $suffix = 2;

        while (
            LatestWorkItem::query()
                ->where('slug', $slug)
                ->when($current, fn ($query) => $query->whereKeyNot($current->getKey()))
                ->exists()
        ) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }

    /**
     * @return Collection<int, LatestWorkCategory>
     */
    private function categoryOptions()
    {
        return LatestWorkCategory::query()->ordered()->get();
    }
}
