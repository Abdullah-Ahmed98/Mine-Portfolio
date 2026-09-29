<?php

namespace App\Console\Commands;

use App\Models\LatestWorkCategory;
use App\Models\LatestWorkItem;
use App\Services\MediaService;
use Illuminate\Console\Command;

class ClearLatestWork extends Command
{
    /**
     * @var string
     */
    protected $signature = 'latest-work:clear {--force : Skip the confirmation prompt}';

    /**
     * @var string
     */
    protected $description = 'Empty the "Latest work" section, removing its items and categories';

    public function handle(MediaService $media): int
    {
        $items = LatestWorkItem::query()->count();
        $categories = LatestWorkCategory::query()->count();

        if ($items === 0 && $categories === 0) {
            $this->components->info('Latest work is already empty.');

            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->components->confirm(
            "Delete {$items} latest work item(s) and {$categories} categor(y/ies)?",
            false,
        )) {
            $this->components->warn('Nothing was deleted.');

            return self::SUCCESS;
        }

        /*
         * Images go with their rows, otherwise every emptied section would leave
         * its uploads behind in storage for no reason.
         */
        foreach (LatestWorkItem::query()->get() as $item) {
            $media->delete($item->image);

            foreach ($item->images as $image) {
                $media->delete($image->path);
            }
        }

        LatestWorkItem::query()->delete();
        LatestWorkCategory::query()->delete();

        $this->components->info("Removed {$items} item(s) and {$categories} categor(y/ies).");
        $this->components->twoColumnDetail('Section', 'now empty, so the page hides it and the nav link goes with it');

        return self::SUCCESS;
    }
}
