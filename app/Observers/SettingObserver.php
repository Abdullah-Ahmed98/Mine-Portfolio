<?php

namespace App\Observers;

use App\Models\Setting;

class SettingObserver
{
    public function saved(): void
    {
        Setting::flushCache();
    }

    public function deleted(): void
    {
        Setting::flushCache();
    }
}
