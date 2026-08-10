<?php

namespace App\Services\DataTable\Filter\SearchFunctions;

use Illuminate\Contracts\Database\Query\Builder;

class FilterNotEquals extends SearchFilter
{
    public function apply(): Builder
    {
        $column = $this->filter->getId();
        $value = $this->filter->getValue();

        return $this->query->whereNot($column, $value);
    }
}
