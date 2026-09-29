<?php

namespace App\Services;

use App\Models\EducationEntry;
use App\Models\Experience;
use App\Models\LatestWorkCategory;
use App\Models\LatestWorkImage;
use App\Models\LatestWorkItem;
use App\Models\ProfileHighlight;
use App\Models\Project;
use App\Models\ProjectCategory;
use App\Models\ProjectImage;
use App\Models\Skill;
use App\Models\SkillCategory;
use App\Models\SocialLink;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * Moves a record one slot up or down within its own list, by swapping
 * sort_order values with the neighbouring row.
 */
class ReorderService
{
    /**
     * Route type => model class, with the column that scopes the list.
     *
     * @var array<string, array{0: class-string<Model>, 1: string|null}>
     */
    private const TYPES = [
        'profile-highlights' => [ProfileHighlight::class, null],
        'social-links' => [SocialLink::class, null],
        'skill-categories' => [SkillCategory::class, null],
        'skills' => [Skill::class, 'skill_category_id'],
        'experiences' => [Experience::class, null],
        'project-categories' => [ProjectCategory::class, null],
        'projects' => [Project::class, null],
        'project-images' => [ProjectImage::class, 'project_id'],
        'education' => [EducationEntry::class, null],
        'latest-work' => [LatestWorkItem::class, null],
        'latest-work-categories' => [LatestWorkCategory::class, null],
        'latest-work-images' => [LatestWorkImage::class, 'latest_work_item_id'],
    ];

    public function move(string $type, int $id, string $direction): bool
    {
        if (! isset(self::TYPES[$type])) {
            throw new InvalidArgumentException("Unknown reorderable type [{$type}].");
        }

        [$modelClass, $scopeColumn] = self::TYPES[$type];

        /** @var Model|null $model */
        $model = $modelClass::query()->find($id);

        if ($model === null) {
            return false;
        }

        $siblings = $modelClass::query()->orderBy('sort_order')->orderBy('id');

        if ($scopeColumn !== null) {
            $siblings->where($scopeColumn, $model->getAttribute($scopeColumn));
        }

        $neighbour = $this->neighbour($siblings, (int) $model->getAttribute('sort_order'), $direction);

        if ($neighbour === null) {
            return false;
        }

        $current = (int) $model->getAttribute('sort_order');
        $target = (int) $neighbour->getAttribute('sort_order');

        if ($current === $target) {
            return false;
        }

        $model->update(['sort_order' => $target]);
        $neighbour->update(['sort_order' => $current]);

        return true;
    }

    /**
     * @param  Builder<Model>  $siblings
     */
    private function neighbour(Builder $siblings, int $current, string $direction): ?Model
    {
        if ($direction === 'up') {
            return (clone $siblings)
                ->where('sort_order', '<', $current)
                ->orderByDesc('sort_order')
                ->first();
        }

        return (clone $siblings)
            ->where('sort_order', '>', $current)
            ->orderBy('sort_order')
            ->first();
    }
}
