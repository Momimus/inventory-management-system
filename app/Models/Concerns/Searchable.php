<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

/**
 * Adds a search() scope that matches a term against the columns listed in the
 * model's $searchable property. The term is passed as a bound parameter, never
 * concatenated into SQL.
 */
trait Searchable
{
    public function scopeSearch(Builder $query, ?string $term): void
    {
        $term = trim((string) $term);

        if ($term === '') {
            return;
        }

        $query->where(function (Builder $query) use ($term) {
            foreach ($this->searchable as $column) {
                $query->orWhereLike($query->qualifyColumn($column), "%{$term}%");
            }
        });
    }
}
