<?php

namespace App\Services\DataTable\Filter\SearchFunctions;

use Illuminate\Contracts\Database\Query\Builder;

class FilterBetween extends SearchFilter
{
    public function apply(): Builder
    {
        $query = $this->query;
        [$minVal, $maxVal] = $this->filter->getValue();

        if ($minVal) {
            if ($this->filter->getDatatype() == 'date') {
                $minVal = $minVal.' 00:00:00';
            }
            $this->filter->setValue($minVal);
            $query = (new FilterGreaterThanOrEqual($this->query, $this->filter))->apply();
        }

        if ($maxVal) {
            if ($this->filter->getDatatype() == 'date') {
                $maxVal = $maxVal.' 23:59:59';
            }
            $this->filter->setValue($maxVal);
            $query = (new FilterLessThanOrEqual($this->query, $this->filter))->apply();
        }

        return $query;
    }
}
