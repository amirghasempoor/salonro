<?php

namespace App\Services\DataTable;

use App\Services\DataTable\Filter\Filter;
use App\Services\DataTable\Sort\Sort;

class DataTableInput
{
    public function __construct(
        private ?int $start,
        private ?int $size,
        private array $filters,
        private ?array $sorting,
        private array $rels,
        private array $allowedFilters,
        private array $allowedSortings,
        private ?array $GroupBy,
    ) {}

    public function getStart(): ?int
    {
        return $this->start;
    }

    public function getSize(): ?int
    {
        return $this->size;
    }

    /**
     * @return array returns an array of Filter objects
     */
    public function getFilters(): array
    {
        $filters = [];

        foreach ($this->filters as $filter) {
            $filters[] = new Filter(
                $filter->id,
                $filter->value,
                $filter->fn,
                $filter->datatype,
                $this->allowedFilters
            );
        }

        return $filters;
    }

    public function getSorting(): ?array
    {
        $sorts = [];
        if (! empty($this->sorting)) {
            foreach ($this->sorting as $sort) {
                $sorts[] = new Sort($sort->id, $sort->desc, $this->allowedSortings);
            }
        }

        return $sorts;
    }

    public function getRelations(): array
    {
        return $this->rels;
    }

    public function getGroupBy(): ?array
    {
        return $this->GroupBy;
    }
}
