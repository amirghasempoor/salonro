<?php

namespace App\Services\DataTable\Filter\SearchFunctions;

use App\Services\DataTable\Enums\DataType;
use Illuminate\Contracts\Database\Query\Builder;

class FilterGreaterThanOrEqual extends SearchFilter
{
    public function apply(): Builder
    {
        $value = ($this->filter->getDatatype() == DataType::NUMERIC) ?
            (float) $this->filter->getValue() : $this->filter->getValue();

        return $this->query->where($this->filter->getId(), '>=', $value);
    }
}
