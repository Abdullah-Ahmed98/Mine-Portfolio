<?php

namespace App\Models;

use App\Models\Concerns\HasSortOrder;
use Database\Factories\EducationEntryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['title', 'institution', 'meta', 'description', 'start_date', 'end_date', 'sort_order'])]
class EducationEntry extends Model
{
    /** @use HasFactory<EducationEntryFactory> */
    use HasFactory, HasSortOrder;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'sort_order' => 'integer',
        ];
    }

    public function dateRange(): string
    {
        if ($this->start_date === null) {
            return (string) $this->meta;
        }

        $start = $this->start_date->format('M Y');
        $end = $this->end_date;

        return $end ? $start.' - '.$end->format($end->year === $this->start_date->year ? 'M Y' : 'Y') : $start;
    }
}
