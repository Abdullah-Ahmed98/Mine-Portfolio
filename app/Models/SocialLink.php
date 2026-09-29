<?php

namespace App\Models;

use App\Models\Concerns\HasSortOrder;
use Database\Factories\SocialLinkFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['platform', 'label', 'url', 'icon', 'is_visible', 'sort_order'])]
class SocialLink extends Model
{
    /** @use HasFactory<SocialLinkFactory> */
    use HasFactory, HasSortOrder;

    /**
     * The platforms the admin panel offers out of the box.
     *
     * @return array<string, string>
     */
    public static function platforms(): array
    {
        return [
            'whatsapp' => 'WhatsApp',
            'linkedin' => 'LinkedIn',
            'github' => 'GitHub',
            'behance' => 'Behance',
            'dribbble' => 'Dribbble',
            'email' => 'Email',
            'x' => 'X (Twitter)',
            'instagram' => 'Instagram',
            'facebook' => 'Facebook',
            'youtube' => 'YouTube',
            'telegram' => 'Telegram',
            'custom' => 'Other',
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_visible' => 'boolean',
        ];
    }

    /**
     * A link is only rendered when it has somewhere to point.
     */
    public function hasDestination(): bool
    {
        return filled($this->url);
    }

    public function isRenderable(): bool
    {
        return $this->is_visible && $this->hasDestination();
    }

    public function resolvedUrl(): ?string
    {
        if (blank($this->url)) {
            return null;
        }

        if ($this->platform === 'email') {
            return str_starts_with($this->url, 'mailto:') ? $this->url : 'mailto:'.$this->url;
        }

        if ($this->platform === 'whatsapp') {
            return $this->whatsappUrl();
        }

        return $this->url;
    }

    /**
     * Accepts a bare phone number, a wa.me link or a full wa.me URL with a prefilled message.
     */
    public function whatsappUrl(): ?string
    {
        $value = trim((string) $this->url);

        if ($value === '') {
            return null;
        }

        if (str_contains($value, 'wa.me')) {
            return $value;
        }

        $digits = preg_replace('/\D+/', '', $value);

        return $digits ? 'https://wa.me/'.$digits : null;
    }

    public function iconKey(): string
    {
        return $this->icon ?: $this->platform;
    }
}
