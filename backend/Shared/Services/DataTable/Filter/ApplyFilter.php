<?php

namespace Shared\Services\DataTable\Filter;

use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use ReflectionMethod;
use ReflectionNamedType;
use Shared\Services\DataTable\Enums\SearchType;
use Shared\Services\DataTable\Exceptions\InvalidFilterException;
use Shared\Services\DataTable\Filter\SearchFunctions\FilterBetween;
use Shared\Services\DataTable\Filter\SearchFunctions\FilterContains;
use Shared\Services\DataTable\Filter\SearchFunctions\FilterEquals;
use Shared\Services\DataTable\Filter\SearchFunctions\FilterGreaterThan;
use Shared\Services\DataTable\Filter\SearchFunctions\FilterLessThan;
use Shared\Services\DataTable\Filter\SearchFunctions\FilterNotEquals;
use Shared\Services\DataTable\Filter\SearchFunctions\SearchFilter;

class ApplyFilter
{
    private SearchFilter $searchFilter;

    public function __construct(
        private Builder $query,
        private Filter $filter,
    ) {}

    /**
     * @throws InvalidFilterException
     */
    public function apply(): Builder
    {
        $filter = $this->filter;
        $query = $this->query;

        $searchType = SearchType::from($filter->getFn());
        switch ($searchType) {
            case SearchType::CONTAINS:
                $this->searchFilter = new FilterContains($query, $filter);
                break;

            case SearchType::EQUALS:
                $this->searchFilter = new FilterEquals($query, $filter);
                break;

            case SearchType::NOT_EQUALS:
                $this->searchFilter = new FilterNotEquals($query, $filter);
                break;

            case SearchType::BETWEEN:
                $this->searchFilter = new FilterBetween($query, $filter);
                break;

            case SearchType::GREATER_THAN:
                $this->searchFilter = new FilterGreaterThan($query, $filter);
                break;

            case SearchType::LESS_THAN:
                $this->searchFilter = new FilterLessThan($query, $filter);
                break;

            default:
                $searchFunction = $filter->getFn();
                throw new InvalidFilterException($searchFunction, "search function `$searchFunction` is invalid.");
        }

        $relation = $this->filter->getRelation();

        return $this->isRelation($relation) ? $this->applyFilterToRelation($relation) : $this->searchFilter->apply();
    }

    /**
     * Only a method that really returns an Eloquent relation counts. A bare
     * method_exists() would accept any method name from the request (delete,
     * save, ...) and whereHas() would then call it on the model.
     */
    private function isRelation(string $name): bool
    {
        $model = $this->query->getModel();

        if ($name === '' || ! method_exists($model, $name)) {
            return false;
        }

        $returnType = (new ReflectionMethod($model, $name))->getReturnType();

        return $returnType instanceof ReflectionNamedType
            && is_subclass_of($returnType->getName(), Relation::class);
    }

    protected function applyFilterToRelation(string $relation): Builder
    {
        return $this->query->whereHas($relation, function (Builder $query) {
            $this->filter->removeRelationFromId();
            $this->applyFilter($query, $this->filter);
        });
    }

    private function applyFilter(Builder $query, Filter $filter): Builder
    {
        return (new ApplyFilter($query, $filter))->apply();
    }
}
