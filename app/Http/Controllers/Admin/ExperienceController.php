<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ExperienceRequest;
use App\Models\Experience;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class ExperienceController extends Controller
{
    public function index(): View
    {
        return view('admin.experiences.index', [
            'experiences' => Experience::query()->ordered()->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.experiences.form', [
            'experience' => new Experience,
        ]);
    }

    public function store(ExperienceRequest $request): RedirectResponse
    {
        Experience::create([
            ...$request->validated(),
            'sort_order' => Experience::nextSortOrder(),
        ]);

        return redirect()->route('admin.experiences.index')->with('status', 'Experience added.');
    }

    public function edit(Experience $experience): View
    {
        return view('admin.experiences.form', [
            'experience' => $experience,
        ]);
    }

    public function update(ExperienceRequest $request, Experience $experience): RedirectResponse
    {
        $experience->update($request->validated());

        return redirect()->route('admin.experiences.index')->with('status', 'Experience updated.');
    }

    public function destroy(Experience $experience): RedirectResponse
    {
        $experience->delete();

        return back()->with('status', 'Experience removed.');
    }
}
