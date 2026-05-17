<?php

namespace App\Services\DataTable\Filter\SearchFunctions;

use App\Services\DataTable\Enums\DataType;
use Illuminate\Contracts\Database\Query\Builder;

class FilterContains extends SearchFilter
{

    public function apply(): Builder
    {
        $column = $this->filter->getId();
        $value = '%' . $this->filter->getValue() . '%';

        if ($this->filter->getDatatype() == DataType::TEXT->value) {
            $query = $this->searchIgnoreCase($column, $value);

        } else {
            $query = $this->query->where($column, 'LIKE', $value);
        }

        return $query;
    }

    private function searchIgnoreCase(string $column, string $value): Builder
    {
        $value = strtolower($value);
        return $this->query->whereRaw("LOWER($column) LIKE ?", [$value]);
    }
}
