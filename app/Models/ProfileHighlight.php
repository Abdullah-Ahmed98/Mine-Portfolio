<?php

namespace App\Models;

use App\Models\Concerns\HasSortOrder;
use Database\Factories\ProfileHighlightFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['profile_id', 'title', 'text', 'sort_order'])]
class ProfileHighlight extends Model
{
    /** @use HasFactory<ProfileHighlightFactory> */
    use HasFactory, HasSortOrder;

    /**
     * @return BelongsTo<Profile, $this>
     */
    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }
}
