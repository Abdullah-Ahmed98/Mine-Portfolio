<?php

namespace App\Models;

use App\Models\Concerns\HasSortOrder;
use Database\Factories\SkillCategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable(['name', 'slug', 'sort_order'])]
class SkillCategory extends Model
{
    /** @use HasFactory<SkillCategoryFactory> */
    use HasFactory, HasSortOrder;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return HasMany<Skill, $this>
     */
    public function skills(): HasMany
    {
        return $this->hasMany(Skill::class)->orderBy('sort_order');
    }

    /**
     * @return HasMany<Skill, $this>
     */
    public function visibleSkills(): HasMany
    {
        return $this->skills()->where('is_visible', true);
    }

    public static function slugFor(string $name): string
    {
        return Str::slug($name) ?: 'category';
    }
}
