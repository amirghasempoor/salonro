<?php

namespace App\Facades\DataTable;

use App\Services\DataTable\DataTableInput;
use App\Services\DataTable\DataTableService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Http\Request;
use App\Services\DataTable\Exceptions\InvalidParameterInterface;

class DataTable
{
    /**
     * Extracts data from request, passes to datatable service and prepares data for response.
     * @param Model|Builder $mixed
     * @param Request $request
     * @param array $allowedFilters
     * @param array $allowedRelations
     * @param array $allowedSortings
     * @param array $allowedSelects
     * @return array
     * @throws InvalidParameterInterface if input parameters are invalid.
     */
    public function run(
        Model|Builder $mixed,
        Request       $request,
        array         $allowedFilters = [],
        array         $allowedRelations = [],
        array         $allowedSortings = [],
        array         $allowedSelects = [],
        array         $allowedGroupBy = []
    ): array
    {

        $filters = json_decode($request->filters);
        $sorting = json_decode($request->sorting);
        $rels = $request->rels ?: array();

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
