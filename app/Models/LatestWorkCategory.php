<?php

namespace App\Models;

use App\Models\Concerns\HasSortOrder;
use Database\Factories\LatestWorkCategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable(['name', 'slug', 'description', 'sort_order'])]
class LatestWorkCategory extends Model
{
    /** @use HasFactory<LatestWorkCategoryFactory> */
    use HasFactory, HasSortOrder;

    public static function slugFor(string $name): string
    {
        return Str::slug($name) ?: 'latest-work';
    }

    /**
     * @return HasMany<LatestWorkItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(LatestWorkItem::class);
    }

    /**
     * @param  Builder<$this>  $query
     */
    public function scopeOrdered(Builder $query): void
    {
        $query->orderBy('sort_order')->orderBy('id');
    }

    /**
     * Only the categories with something visible to show are worth offering, so
     * the admin's type picker never lists an empty heading.
     *
     * @param  Builder<$this>  $query
     */
    public function scopeWithVisibleItems(Builder $query): void
    {
        $query->whereHas('items', fn (Builder $items) => $items->where('is_visible', true));
    }
}
