<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

/**
 * Shared sort-order behaviour for the admin's reorderable lists.
 */
trait HasSortOrder
{
    /**
     * The next free position, so a new record lands at the end of the list.
     *
     * An empty list starts at zero rather than one, which keeps the column a
     * zero-based sequence no matter how the list was built.
     *
     * @param  Builder<static>|Relation<*, static, *>|null  $scope  Restrict to a
     *                                                            subset, such as
     *                                                            one parent's
     *                                                            children.
     */
    public static function nextSortOrder(Builder|Relation|null $scope = null): int
    {
        $query = $scope instanceof Relation ? $scope->getQuery() : $scope;

        $highest = ($query ?? static::query())->max('sort_order');

        return $highest === null ? 0 : ((int) $highest) + 1;
    }
}
