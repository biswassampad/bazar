<?php

namespace Bazar\Filters;

use Bazar\Models\User as Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class User extends Filter
{
    /**
     * Apply the filter on the query.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  \Illuminate\Http\Request  $request
     * @param  mixed  $value
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function apply(Builder $query, Request $request, $value): Builder
    {
        return $query->whereHas('user', function (Builder $query) use ($value) {
            return $query->where('users.id', $value);
        });
    }

    /**
     * Get filter options.
     *
     * @return array
     */
    public function options(): array
    {
        return Model::pluck('name', 'id')->toArray();
    }
}
