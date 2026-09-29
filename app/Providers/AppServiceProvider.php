<?php

namespace App\Providers;

use App\Models\Setting;
use App\Observers\SettingObserver;
use App\Services\MediaService;
use App\View\Composers\PortfolioComposer;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        // The public layout, its partials and the page templates all read the
        // profile, so the composer is attached to each of them.
        View::composer('components.layouts.app', PortfolioComposer::class);
        View::composer('pages.*', PortfolioComposer::class);
        View::composer('partials.*', PortfolioComposer::class);

        // Settings are read on every page, so drop the cache on any write.
        Setting::observe(SettingObserver::class);

        $this->app->scoped(MediaService::class);
    }
}
