<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SkillRequest;
use App\Models\Skill;
use App\Models\SkillCategory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;

class SkillController extends Controller
{
    public function index(): View
    {
        return view('admin.skills.index', [
            'categories' => SkillCategory::query()
                ->with(['skills' => fn ($query) => $query->orderBy('sort_order')])
                ->orderBy('sort_order')
                ->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.skills.form', [
            'skill' => new Skill,
            'categories' => $this->categoryOptions(),
        ]);
    }

    public function store(SkillRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        Skill::create([
            ...$validated,
            'sort_order' => Skill::nextSortOrder(
                Skill::query()->where('skill_category_id', $validated['skill_category_id'])
            ),
        ]);

        return redirect()->route('admin.skills.index')->with('status', 'Skill added.');
    }

    public function edit(Skill $skill): View
    {
        return view('admin.skills.form', [
            'skill' => $skill,
            'categories' => $this->categoryOptions(),
        ]);
    }

    public function update(SkillRequest $request, Skill $skill): RedirectResponse
    {
        $skill->update($request->validated());

        return redirect()->route('admin.skills.index')->with('status', 'Skill updated.');
    }

    public function destroy(Skill $skill): RedirectResponse
    {
        $skill->delete();

        return back()->with('status', 'Skill removed.');
    }

    /**
     * @return Collection<int, SkillCategory>
     */
    private function categoryOptions()
    {
        return SkillCategory::query()->orderBy('sort_order')->get();
    }
}
