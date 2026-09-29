<?php

namespace App\View\Composers;

use App\Services\PortfolioService;
use Illuminate\View\View;

/**
 * Shares the profile and its social links with every public view so layouts and
 * partials never have to reach for a service themselves.
 */
class PortfolioComposer
{
    public function __construct(private readonly PortfolioService $portfolio) {}

    public function compose(View $view): void
    {
        $view->with([
            'profile' => $this->portfolio->profile(),
            'socialLinks' => $this->portfolio->socialLinks(),
        ]);
    }
}
