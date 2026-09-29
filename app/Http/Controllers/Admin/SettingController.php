<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SettingsRequest;
use App\Models\Setting;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class SettingController extends Controller
{
    public function index(): View
    {
        $settings = Setting::query()->orderBy('group')->orderBy('sort_order')->get();

        return view('admin.settings.index', [
            'grouped' => $settings->groupBy('group'),
            'groups' => Setting::groups(),
        ]);
    }

    public function update(SettingsRequest $request): RedirectResponse
    {
        foreach ($request->values() as $key => $value) {
            Setting::query()->updateOrCreate(['key' => $key], ['value' => $value]);
        }

        Setting::flushCache();

        return back()->with('status', 'Settings saved.');
    }
}
