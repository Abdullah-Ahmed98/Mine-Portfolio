<?php

namespace App\Models;

use App\Support\Paragraphs;
use Database\Factories\ProfileFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'full_name',
    'title',
    'role_short',
    'short_intro',
    'full_description',
    'email',
    'phone',
    'location',
    'years_experience',
    'availability_text',
    'availability_status',
    'profile_image',
    'cv_path',
])]
class Profile extends Model
{
    /** @use HasFactory<ProfileFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'availability_status' => 'boolean',
        ];
    }

    /**
     * @return HasMany<ProfileHighlight, $this>
     */
    public function highlights(): HasMany
    {
        return $this->hasMany(ProfileHighlight::class)->orderBy('sort_order');
    }

    /**
     * The single profile row that the whole site reads from.
     */
    public static function current(): self
    {
        return static::query()->firstOrCreate(
            ['id' => 1],
            ['full_name' => 'Your Name', 'title' => 'Your Title'],
        );
    }

    public function profileImageUrl(): ?string
    {
        return $this->profile_image ? Storage::disk('public')->url($this->profile_image) : null;
    }

    public function cvUrl(): ?string
    {
        return $this->cv_path ? route('resume') : null;
    }

    public function hasCv(): bool
    {
        return filled($this->cv_path);
    }

    /**
     * The full description is stored as one text column; render it as paragraphs.
     *
     * @return array<int, string>
     *
     * @see Paragraphs::split()
     */
    public function descriptionParagraphs(): array
    {
        return Paragraphs::split($this->full_description);
    }

    /**
     * @return array<int, array{title: string, text: string}>
     */
    public function traitCards(): array
    {
        return $this->highlights
            ->map(fn (ProfileHighlight $highlight) => [
                'title' => $highlight->title,
                'text' => $highlight->text,
            ])
            ->all();
    }
}
