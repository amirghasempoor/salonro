<?php

namespace Shared\Services\DataTable\Filter\SearchFunctions;

use Illuminate\Contracts\Database\Query\Builder;
use Shared\Services\DataTable\Filter\Filter;

abstract class SearchFilter
{
    public function __construct(
        protected Builder $query,
        protected Filter $filter,
    ) {}

    abstract public function apply(): Builder;
}
