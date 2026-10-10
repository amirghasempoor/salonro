<?php

namespace Shared\Facades\DataTable;

use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Shared\Services\DataTable\DataTableInput;
use Shared\Services\DataTable\DataTableService;
use Shared\Services\DataTable\Exceptions\InvalidParameterInterface;

class DataTable
{
    /**
     * Extracts data from request, passes to datatable service and prepares data for response.
     *
     * @throws InvalidParameterInterface if input parameters are invalid.
     */
    public function run(
        Model|Builder $mixed,
        Request $request,
        array $allowedFilters = [],
        array $allowedRelations = [],
        array $allowedSortings = [],
        array $allowedSelects = [],
        array $allowedGroupBy = []
    ): array {

        $filters = json_decode($request->filters);
        $sorting = json_decode($request->sorting);
        $rels = $request->rels ?: [];

        $dataTableInput = new DataTableInput(
            $request->start,
            $request->size,
            $filters,
            $sorting,
            $rels,
            $allowedFilters,
            $allowedSortings,
            $allowedGroupBy
        );

        $query = $this->makeQueryFromModel($mixed);

        $dataTableService = (new DataTableService($query, $dataTableInput))
            ->setAllowedFilters($allowedFilters)
            ->setAllowedRelations($allowedRelations)
            ->setAllowedSortings($allowedSortings)
            ->setAllowedSelects($allowedSelects)
            ->setAllowedGroupBy($allowedGroupBy);

        return $dataTableService->getData();
    }

    protected function makeQueryFromModel(Model|Builder $mixed): Builder
    {
        return ($mixed instanceof Model) ? $mixed->query() : $mixed;
    }
}
