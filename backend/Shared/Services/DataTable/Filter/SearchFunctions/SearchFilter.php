<?php

namespace Shared\Services\DataTable\Filter\SearchFunctions;

use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Shared\Services\DataTable\Filter\Filter;

abstract class SearchFilter
{
    public function __construct(
        protected Builder $query,
        protected Filter $filter,
    ) {}

    abstract public function apply(): Builder;

    /**
     * Quote a column name for use inside a raw SQL fragment. The column comes from
     * the request, so it must never be concatenated into SQL as it is.
     */
    protected function wrapColumn(string $column): string
    {
        $base = $this->query instanceof EloquentBuilder ? $this->query->getQuery() : $this->query;

        return $base->getGrammar()->wrap($column);
    }
}
