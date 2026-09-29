<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SkillCategoryRequest;
use App\Models\SkillCategory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class SkillCategoryController extends Controller
{
    public function index(): View
    {
        return view('admin.skill-categories.index', [
            'categories' => SkillCategory::query()
                ->withCount('skills')
                ->orderBy('sort_order')
                ->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.skill-categories.form', [
            'category' => new SkillCategory,
        ]);
    }

    public function store(SkillCategoryRequest $request): RedirectResponse
    {
        SkillCategory::create([
            ...$request->validated(),
            'sort_order' => SkillCategory::nextSortOrder(),
        ]);

        return redirect()->route('admin.skill-categories.index')->with('status', 'Category added.');
    }

    public function edit(SkillCategory $skillCategory): View
    {
        return view('admin.skill-categories.form', [
            'category' => $skillCategory,
        ]);
    }

    public function update(SkillCategoryRequest $request, SkillCategory $skillCategory): RedirectResponse
    {
        $skillCategory->update($request->validated());

        return redirect()->route('admin.skill-categories.index')->with('status', 'Category updated.');
    }

    public function destroy(SkillCategory $skillCategory): RedirectResponse
    {
        $skillCategory->delete();

        return back()->with('status', 'Category removed along with its skills.');
    }
}
