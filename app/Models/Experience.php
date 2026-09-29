<?php

namespace App\Models;

use App\Models\Concerns\HasSortOrder;
use Database\Factories\ExperienceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'company',
    'position',
    'location',
    'start_date',
    'end_date',
    'is_current',
    'date_label',
    'description',
    'responsibilities',
    'technologies',
    'company_url',
    'sort_order',
])]
class Experience extends Model
{
    /** @use HasFactory<ExperienceFactory> */
    use HasFactory, HasSortOrder;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'is_current' => 'boolean',
            'sort_order' => 'integer',
            'responsibilities' => 'array',
            'technologies' => 'array',
        ];
    }

    /**
     * @param  Builder<$this>  $query
     */
    public function scopeOrdered(Builder $query): void
    {
        $query->orderByDesc('start_date')->orderBy('sort_order');
    }

    /**
     * A human readable date range, e.g. "Aug 2020 - 2022" or "Present".
     */
    public function dateRange(): string
    {
        if (filled($this->date_label)) {
            return $this->date_label;
        }

        if ($this->start_date === null) {
            return '';
        }

        $start = $this->start_date->format('M Y');

        if ($this->is_current) {
            return $start.' - Present';
        }

        $end = $this->end_date;

        return $end ? $start.' - '.$end->format($end->year === $this->start_date->year ? 'M Y' : 'Y') : $start;
    }

    /**
     * @return array<int, string>
     */
    public function responsibilityList(): array
    {
        return collect($this->responsibilities)
            ->map(fn (string $item) => trim($item))
            ->filter()
            ->values()
            ->all();
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
}
