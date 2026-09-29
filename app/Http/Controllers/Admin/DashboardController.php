<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use App\Models\EducationEntry;
use App\Models\Experience;
use App\Models\LatestWorkItem;
use App\Models\Profile;
use App\Models\Project;
use App\Models\SocialLink;
use App\Services\ReorderService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(): View
    {
        return view('admin.dashboard', [
            'profile' => Profile::current(),
            'counts' => [
                'Projects' => Project::query()->count(),
                'Featured' => Project::query()->where('is_featured', true)->count(),
                'Latest work' => LatestWorkItem::query()->count(),
                'Experience' => Experience::query()->count(),
                'Education' => EducationEntry::query()->count(),
                'Social links' => SocialLink::query()->count(),
                'Unread messages' => ContactMessage::query()->unread()->count(),
            ],
            'recentMessages' => ContactMessage::query()->latest()->limit(5)->get(),
        ]);
    }

    public function reorder(Request $request, ReorderService $reorder): RedirectResponse
    {
        $validated = $request->validate([
            'direction' => ['required', 'in:up,down'],
        ]);

        $reorder->move($request->route('type'), (int) $request->route('id'), $validated['direction']);

        return back()->with('status', 'Order updated.');
    }
}
