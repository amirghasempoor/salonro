<?php

namespace Shared\Facades\DataTable;

use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Shared\Services\DataTable\DataTableInput;
use Shared\Services\DataTable\DataTableService;
use Shared\Services\DataTable\Exceptions\InvalidPagingException;
use Shared\Services\DataTable\Exceptions\InvalidParameterInterface;

class DataTable
{
    /**
     * The most rows one request may ask for.
     */
    private const int MAX_SIZE = 500;

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

        // Both are optional: a missing, malformed or non-array value means "no filters / no sorting".
        $filters = json_decode((string) $request->filters);
        $filters = is_array($filters) ? $filters : [];
        $sorting = json_decode((string) $request->sorting);
        $sorting = is_array($sorting) ? $sorting : [];
        $rels = is_array($request->rels) ? $request->rels : [];

        foreach ($filters as $filter) {
            if (! is_object($filter) || array_diff(['id', 'value', 'fn', 'datatype'], array_keys(get_object_vars($filter))) !== []) {
                throw new InvalidPagingException('Each filter needs id, value, fn and datatype.');
            }
        }

        foreach ($sorting as $sort) {
            if (! is_object($sort) || ! isset($sort->id) || ! is_string($sort->id) || ! isset($sort->desc)) {
                throw new InvalidPagingException('Each sorting needs id and desc.');
            }
        }

        $dataTableInput = new DataTableInput(
            $this->wholeNumber($request->start, 'start', min: 0),
            $this->wholeNumber($request->size, 'size', min: 1, max: self::MAX_SIZE),
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

    /**
     * An optional whole number inside bounds; anything else is a bad request, not a 500.
     *
     * @throws InvalidPagingException
     */
    private function wholeNumber(mixed $value, string $name, int $min, ?int $max = null): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        $number = filter_var($value, FILTER_VALIDATE_INT);

        if ($number === false || $number < $min || ($max !== null && $number > $max)) {
            throw new InvalidPagingException("The {$name} value is invalid.");
        }

        return $number;
    }

    protected function makeQueryFromModel(Model|Builder $mixed): Builder
    {
        return ($mixed instanceof Model) ? $mixed->query() : $mixed;
    }
}
