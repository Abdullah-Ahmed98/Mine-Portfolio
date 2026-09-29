<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SocialLinkRequest;
use App\Models\SocialLink;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class SocialLinkController extends Controller
{
    public function index(): View
    {
        return view('admin.social-links.index', [
            'socialLinks' => SocialLink::query()->orderBy('sort_order')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.social-links.form', [
            'socialLink' => new SocialLink,
        ]);
    }

    public function store(SocialLinkRequest $request): RedirectResponse
    {
        SocialLink::create([
            ...$request->validated(),
            'sort_order' => SocialLink::nextSortOrder(),
        ]);

        return redirect()->route('admin.social-links.index')->with('status', 'Link added.');
    }

    public function edit(SocialLink $socialLink): View
    {
        return view('admin.social-links.form', [
            'socialLink' => $socialLink,
        ]);
    }

    public function update(SocialLinkRequest $request, SocialLink $socialLink): RedirectResponse
    {
        $socialLink->update($request->validated());

        return redirect()->route('admin.social-links.index')->with('status', 'Link updated.');
    }

    public function destroy(SocialLink $socialLink): RedirectResponse
    {
        $socialLink->delete();

        return back()->with('status', 'Link removed.');
    }
}
