<?php

namespace App\Models;

use App\Models\Concerns\HasSortOrder;
use Database\Factories\LatestWorkItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * One entry in the "Latest work" strip on the home page.
 *
 * Deliberately separate from Project: a project belongs to a showcase section
 * and is written up at length, while a latest-work entry is a short, recent
 * credit with a title, a type and a link. They are managed independently so a
 * single project can appear in both, or in neither.
 */
#[Fillable([
    'latest_work_category_id',
    'title',
    'slug',
    'short_description',
    'details',
    'image',
    'project_url',
    'technologies',
    'is_featured',
    'is_visible',
    'sort_order',
])]
class LatestWorkItem extends Model
{
    /** @use HasFactory<LatestWorkItemFactory> */
    use HasFactory, HasSortOrder;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'technologies' => 'array',
            'is_featured' => 'boolean',
            'is_visible' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<LatestWorkCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(LatestWorkCategory::class, 'latest_work_category_id');
    }

    /**
     * @return HasMany<LatestWorkImage, $this>
     */
    public function images(): HasMany
    {
        return $this->hasMany(LatestWorkImage::class)->orderBy('sort_order');
    }

    /**
     * @param  Builder<$this>  $query
     */
    public function scopeVisible(Builder $query): void
    {
        $query->where('is_visible', true);
    }

    /**
     * @param  Builder<$this>  $query
     */
    public function scopeFeatured(Builder $query): void
    {
        $query->where('is_featured', true);
    }

    /**
     * @param  Builder<$this>  $query
     */
    public function scopeOrdered(Builder $query): void
    {
        $query->orderBy('sort_order')->orderBy('id');
    }

    public static function slugFor(string $title): string
    {
        return Str::slug($title) ?: 'latest-work-item';
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function coverImageUrl(): ?string
    {
        return $this->image ? Storage::disk('public')->url($this->image) : null;
    }

    public function hasLinks(): bool
    {
        return filled($this->project_url);
    }

    /**
     * The type label shown under the title, if a category is set.
     */
    public function typeLabel(): ?string
    {
        return $this->category?->name;
    }

    /**
     * @return array<int, string>
     */
    public function technologyList(): array
    {
        return collect($this->technologies)
            ->map(fn (string $item) => trim($item))
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    public function detailParagraphs(): array
    {
        $source = filled($this->details) ? $this->details : $this->short_description;

        return collect(preg_split('/\R{2,}/', (string) $source))
            ->map(fn (string $paragraph) => trim($paragraph))
            ->filter()
            ->values()
            ->all();
    }

    /**
     * The anchor this item is rendered under on the single page.
     *
     * Nothing here has a page of its own, so the anchor is the only way to link
     * straight to one from the admin.
     */
    public function anchor(): string
    {
        return 'latest-work-'.$this->slug;
    }
}
