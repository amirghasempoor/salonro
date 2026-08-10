<?php

namespace App\Services\DataTable;

use App\Services\DataTable\Filter\ApplyFilter;
use App\Services\DataTable\Sort\ApplySort;
use Illuminate\Contracts\Database\Query\Builder;

class DataTableService
{
    protected array $allowedFilters;

    protected array $allowedRelations;

    protected array $allowedSortings;

    protected array $allowedSelects;

    protected array $allowedGroupBy;

    private int $totalRowCount;

    public function __construct(
        protected Builder $query,
        private DataTableInput $dataTableInput
    ) {}

    public function setAllowedFilters(array $allowedFilters): DataTableService
    {
        $this->allowedFilters = $allowedFilters;

        return $this;
    }

    public function setAllowedRelations(array $allowedRelations): DataTableService
    {
        $this->allowedRelations = $allowedRelations;

        return $this;
    }

    public function setAllowedSortings(array $allowedSortings): DataTableService
    {
        $this->allowedSortings = $allowedSortings;

        return $this;
    }

    public function setAllowedSelects(array $allowedSelects): DataTableService
    {
        $this->allowedSelects = $allowedSelects;

        return $this;
    }

    public function setAllowedGroupBy(array $allowedGroupBy): DataTableService
    {
        $this->allowedGroupBy = $allowedGroupBy;

        return $this;
    }

    /**
     * Handle 'getData' operations
     */
    public function getData(): array
    {
        $query = $this->buildQuery();
        $data = $query->get();

        return [
            'data' => $data,
            'meta' => [
                'totalRowCount' => $this->totalRowCount,
            ],
        ];
    }

    protected function buildQuery(): Builder
    {
        $query = $this->query;

        foreach ($this->dataTableInput->getFilters() as $filter) {
            $query = (new ApplyFilter($query, $filter))->apply();
        }

        $query = $this->applySelect($query, $this->allowedSelects);
        $query = $this->includeRelationsInQuery($query, $this->allowedRelations);
        $query = $this->applyGroupBy($query, $this->allowedGroupBy);

        $this->totalRowCount = $query->count();

        if (! is_null($this->dataTableInput->getStart())) {
            $query->offset($this->dataTableInput->getStart());
        }

        if (! is_null($this->dataTableInput->getSize())) {
            $query->limit($this->dataTableInput->getSize());
        }

        $sorting = $this->dataTableInput->getSorting();
        foreach ($sorting as $sort) {
            $query = (new ApplySort($query, $sort))->apply();
        }

        return $query;
    }

    protected function applySelect(Builder $query, array $selectedFields): Builder
    {
        if (! empty($selectedFields)) {
            $query->select($selectedFields);
        }

        return $query;
    }

    protected function applyGroupBy(Builder $query, array $groupBy): Builder
    {
        return empty($groupBy) ? $query : $query->groupBy($groupBy);
    }

    protected function includeRelationsInQuery(Builder $query, array $rels): Builder
    {
        if (! empty($rels)) {
            $query->with($rels);
        }

        return $query;
    }

    // (later) define mapping of relation names to prevent relation name expose.
    // (later) define mapping of column names to prevent column name expose.
}
