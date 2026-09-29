<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProjectCategoryRequest;
use App\Models\ProjectCategory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class ProjectCategoryController extends Controller
{
    public function index(): View
    {
        return view('admin.project-categories.index', [
            'categories' => ProjectCategory::query()
                ->withCount('projects')
                ->orderBy('sort_order')
                ->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.project-categories.form', [
            'category' => new ProjectCategory,
        ]);
    }

    public function store(ProjectCategoryRequest $request): RedirectResponse
    {
        ProjectCategory::create([
            ...$request->validated(),
            'sort_order' => ProjectCategory::nextSortOrder(),
        ]);

        return redirect()->route('admin.project-categories.index')->with('status', 'Category added.');
    }

    public function edit(ProjectCategory $projectCategory): View
    {
        return view('admin.project-categories.form', [
            'category' => $projectCategory,
        ]);
    }

    public function update(ProjectCategoryRequest $request, ProjectCategory $projectCategory): RedirectResponse
    {
        $projectCategory->update($request->validated());

        return redirect()->route('admin.project-categories.index')->with('status', 'Category updated.');
    }

    public function destroy(ProjectCategory $projectCategory): RedirectResponse
    {
        $projectCategory->delete();

        return back()->with('status', 'Category removed. Its projects were kept.');
    }
}
