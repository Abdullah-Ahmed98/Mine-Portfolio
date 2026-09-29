<?php

namespace App\Http\Controllers;

use App\Services\PortfolioService;
use App\Support\Seo;
use App\Support\SinglePage;
use Illuminate\Contracts\View\View;

/**
 * Renders the whole public site: one page, every section an anchor on it.
 */
class HomeController extends Controller
{
    public function __invoke(PortfolioService $portfolio): View
    {
        $profile = $portfolio->profile();
        $showcase = $portfolio->showcaseSections();
        $latestWork = $portfolio->latestWork();
        $skillGroups = $portfolio->skillGroups();
        $experiences = $portfolio->experiences();
        $education = $portfolio->education();

        return view('pages.single', [
            'profile' => $profile,
            'highlights' => $profile->highlights,
            'showcase' => $showcase,
            'latestWork' => $latestWork,
            // Flattened for the JSON-LD ItemList, which describes the work the
            // single page has no per-project URL to index.
            'showcaseProjects' => $showcase->flatMap(fn ($category) => $category->projects),
            // The page skips a section when it has no content, so the navigation
            // has to skip it too rather than link to an anchor that is not there.
            'navItems' => SinglePage::navigation($showcase, [
                'latest-work' => $latestWork->isNotEmpty(),
                'skills' => $skillGroups->isNotEmpty(),
                'experience' => $experiences->isNotEmpty(),
            ]),
            'experiences' => $experiences,
            'skillGroups' => $skillGroups,
            'education' => $education,
            'stats' => [
                'years' => $profile->years_experience,
                'projects' => $showcase->sum(fn ($category) => $category->projects->count()),
                'roles' => $experiences->count(),
            ],
            'seo' => Seo::make(
                Seo::defaultTitle(),
                Seo::defaultDescription(),
                $profile->profileImageUrl(),
            ),
        ]);
    }
}
