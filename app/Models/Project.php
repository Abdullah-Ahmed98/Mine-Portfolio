<?php

namespace App\Models;

use App\Models\Concerns\HasSortOrder;
use Database\Factories\ProjectFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

#[Fillable([
    'project_category_id',
    'title',
    'slug',
    'client_name',
    'client_role',
    'short_description',
    'full_description',
    'featured_image',
    'project_url',
    'github_url',
    'completed_at',
    'technologies',
    'is_featured',
    'is_published',
    'sort_order',
])]
class Project extends Model
{
    /** @use HasFactory<ProjectFactory> */
    use HasFactory, HasSortOrder;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'completed_at' => 'date',
            'technologies' => 'array',
            'is_featured' => 'boolean',
            'is_published' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<ProjectCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ProjectCategory::class, 'project_category_id');
    }

    /**
     * @return HasMany<ProjectImage, $this>
     */
    public function images(): HasMany
    {
        return $this->hasMany(ProjectImage::class)->orderBy('sort_order');
    }

    /**
     * @param  Builder<$this>  $query
     */
    public function scopePublished(Builder $query): void
    {
        $query->where('is_published', true);
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
        $query->orderBy('sort_order')->orderByDesc('completed_at');
    }

    public static function slugFor(string $title): string
    {
        return Str::slug($title) ?: 'project';
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function coverImage(): ?string
    {
        return $this->featured_image;
    }

    public function coverImageUrl(): ?string
    {
        return $this->featured_image ? Storage::disk('public')->url($this->featured_image) : null;
    }

    public function hasLinks(): bool
    {
        return filled($this->project_url) || filled($this->github_url);
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
    public function descriptionParagraphs(): array
    {
        $source = filled($this->full_description) ? $this->full_description : $this->short_description;

        return collect(preg_split('/\R{2,}/', (string) $source))
            ->map(fn (string $paragraph) => trim($paragraph))
            ->filter()
            ->values()
            ->all();
    }

    public function yearLabel(): ?string
    {
        return $this->completed_at?->format('Y');
    }

    /**
     * The anchor this project is rendered under on the single page.
     *
     * Projects have no page of their own, so the anchor is the only way to link
     * straight to one from the admin. It is namespaced by category slug to stay
     * unique now that every project on the site shares a single document.
     */
    public function anchor(): string
    {
        return $this->category?->slug.'-'.$this->slug;
    }
}
