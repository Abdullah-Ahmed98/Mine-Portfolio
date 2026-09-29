<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LatestWorkCategoryRequest;
use App\Models\LatestWorkCategory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class LatestWorkCategoryController extends Controller
{
    public function index(): View
    {
        return view('admin.latest-work-categories.index', [
            'categories' => LatestWorkCategory::query()
                ->withCount('items')
                ->ordered()
                ->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.latest-work-categories.form', [
            'category' => new LatestWorkCategory,
        ]);
    }

    public function store(LatestWorkCategoryRequest $request): RedirectResponse
    {
        LatestWorkCategory::create([
            ...$request->validated(),
            'sort_order' => LatestWorkCategory::nextSortOrder(),
        ]);

        return redirect()->route('admin.latest-work-categories.index')->with('status', 'Type added.');
    }

    public function edit(LatestWorkCategory $latestWorkCategory): View
    {
        return view('admin.latest-work-categories.form', [
            'category' => $latestWorkCategory,
        ]);
    }

    public function update(LatestWorkCategoryRequest $request, LatestWorkCategory $latestWorkCategory): RedirectResponse
    {
        $latestWorkCategory->update($request->validated());

        return redirect()->route('admin.latest-work-categories.index')->with('status', 'Type updated.');
    }

    public function destroy(LatestWorkCategory $latestWorkCategory): RedirectResponse
    {
        $latestWorkCategory->delete();

        return back()->with('status', 'Type removed. Its items were kept.');
    }
}
