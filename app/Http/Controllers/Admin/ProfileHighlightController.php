<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProfileHighlightRequest;
use App\Models\Profile;
use App\Models\ProfileHighlight;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class ProfileHighlightController extends Controller
{
    public function index(): View
    {
        return view('admin.highlights.index', [
            'highlights' => Profile::current()->highlights,
        ]);
    }

    public function create(): View
    {
        return view('admin.highlights.form', [
            'highlight' => new ProfileHighlight,
        ]);
    }

    public function store(ProfileHighlightRequest $request): RedirectResponse
    {
        $profile = Profile::current();

        $profile->highlights()->create([
            ...$request->validated(),
            'sort_order' => ProfileHighlight::nextSortOrder($profile->highlights()),
        ]);

        return redirect()->route('admin.highlights.index')->with('status', 'Highlight added.');
    }

    public function edit(ProfileHighlight $highlight): View
    {
        return view('admin.highlights.form', [
            'highlight' => $highlight,
        ]);
    }

    public function update(ProfileHighlightRequest $request, ProfileHighlight $highlight): RedirectResponse
    {
        $highlight->update($request->validated());

        return redirect()->route('admin.highlights.index')->with('status', 'Highlight updated.');
    }

    public function destroy(ProfileHighlight $highlight): RedirectResponse
    {
        $highlight->delete();

        return back()->with('status', 'Highlight removed.');
    }
}
