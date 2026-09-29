<?php

namespace App\Models;

use App\Models\Concerns\HasSortOrder;
use Database\Factories\LatestWorkImageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

#[Fillable(['latest_work_item_id', 'path', 'alt', 'is_cover', 'sort_order'])]
class LatestWorkImage extends Model
{
    /** @use HasFactory<LatestWorkImageFactory> */
    use HasFactory, HasSortOrder;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_cover' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<LatestWorkItem, $this>
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(LatestWorkItem::class, 'latest_work_item_id');
    }

    public function url(): string
    {
        return Storage::disk('public')->url($this->path);
    }
}
