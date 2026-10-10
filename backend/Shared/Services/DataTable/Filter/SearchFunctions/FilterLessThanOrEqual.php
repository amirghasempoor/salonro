<?php

namespace Shared\Services\DataTable\Filter\SearchFunctions;

use Illuminate\Contracts\Database\Query\Builder;
use Shared\Services\DataTable\Enums\DataType;

class FilterLessThanOrEqual extends SearchFilter
{
    public function apply(): Builder
    {
        $value = ($this->filter->getDatatype() == DataType::NUMERIC) ?
            (float) $this->filter->getValue() : $this->filter->getValue();

        return $this->query->where($this->filter->getId(), '<=', $value);
    }
}
