<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateProfileRequest;
use App\Models\Profile;
use App\Services\MediaService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function edit(): View
    {
        return view('admin.profile.edit', [
            'profile' => Profile::current(),
        ]);
    }

    public function update(UpdateProfileRequest $request, MediaService $media): RedirectResponse
    {
        $profile = Profile::current();
        $validated = $request->validated();

        if ($request->boolean('remove_image')) {
            $media->delete($profile->profile_image);
            $validated['profile_image'] = null;
        } elseif ($request->hasFile('profile_image')) {
            $validated['profile_image'] = $media->replace(
                $profile->profile_image,
                $request->file('profile_image'),
                'portfolio/profile',
            );
        }

        if ($request->boolean('remove_cv')) {
            if ($profile->cv_path && Storage::disk('public')->exists($profile->cv_path)) {
                Storage::disk('public')->delete($profile->cv_path);
            }
            $validated['cv_path'] = null;
        } elseif ($request->hasFile('cv')) {
            if ($profile->cv_path && Storage::disk('public')->exists($profile->cv_path)) {
                Storage::disk('public')->delete($profile->cv_path);
            }

            $file = $request->file('cv');
            $validated['cv_path'] = $file->store('portfolio/cv', 'public');
        }

        $profile->update($validated);

        return back()->with('status', 'Profile updated.');
    }
}
