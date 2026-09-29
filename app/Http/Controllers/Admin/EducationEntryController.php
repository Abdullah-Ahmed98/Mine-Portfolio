<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\EducationEntryRequest;
use App\Models\EducationEntry;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class EducationEntryController extends Controller
{
    public function index(): View
    {
        return view('admin.education.index', [
            'entries' => EducationEntry::query()->orderBy('sort_order')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.education.form', [
            'entry' => new EducationEntry,
        ]);
    }

    public function store(EducationEntryRequest $request): RedirectResponse
    {
        EducationEntry::create([
            ...$request->validated(),
            'sort_order' => EducationEntry::nextSortOrder(),
        ]);

        return redirect()->route('admin.education.index')->with('status', 'Entry added.');
    }

    /**
     * The route parameter is named after the resource, not the model, so
     * implicit binding is matched on $education rather than $educationEntry.
     */
    public function edit(EducationEntry $education): View
    {
        return view('admin.education.form', [
            'entry' => $education,
        ]);
    }

    public function update(EducationEntryRequest $request, EducationEntry $education): RedirectResponse
    {
        $education->update($request->validated());

        return redirect()->route('admin.education.index')->with('status', 'Entry updated.');
    }

    public function destroy(EducationEntry $education): RedirectResponse
    {
        $education->delete();

        return back()->with('status', 'Entry removed.');
    }
}
