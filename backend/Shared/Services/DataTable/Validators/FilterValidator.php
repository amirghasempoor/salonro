<?php

namespace Shared\Services\DataTable\Validators;

use Shared\Services\DataTable\Enums\DataType;
use Shared\Services\DataTable\Enums\SearchType;
use Shared\Services\DataTable\Exceptions\InvalidFilterException;
use Shared\Services\DataTable\Filter\Filter;

class FilterValidator
{
    private static $instance;

    private function __construct() {}

    public static function getInstance(): FilterValidator
    {
        if (! isset(self::$instance)) {
            self::$instance = new static;
        }

        return self::$instance;
    }

    public function isValid(Filter $filter, array $allowedFilters): bool
    {
        if (! $this->isAllowed($filter, $allowedFilters)) {
            $filterId = $filter->getId();
            throw new InvalidFilterException($filter->getId(), "filtering field `$filterId` is not allowed.");
        }

        if (! $this->isValidSearchFunction($filter)) {
            $searchFunction = $filter->getFn();
            throw new InvalidFilterException($searchFunction, "search function `$searchFunction` is invalid.");
        }

        if ($this->isValidDataType($filter) == -1) {
            throw new InvalidFilterException(null, 'datatype property is not set in `filters` array.');
        }

        if (! $this->isValidDataType($filter)) {
            $datatype = $filter->getDatatype();
            throw new InvalidFilterException($datatype, "datatype `$datatype` is invalid.");
        }

        return true;
    }

    /**
     * Columns that must never be filterable, however the endpoint is configured:
     * a filter is an oracle, so letting a client match against a password hash or
     * a token one prefix at a time would disclose it.
     */
    private const array SENSITIVE_COLUMNS = [
        'password', 'remember_token', 'token', 'verification_code', 'api_token',
    ];

    protected function isAllowed(Filter $filter, array $allowedFilters): bool
    {
        if (! $this->isSafeIdentifier($filter->getId())) {
            return false;
        }

        if (in_array(strtolower($filter->getColumn()), self::SENSITIVE_COLUMNS, true)) {
            return false;
        }

        return $allowedFilters == ['*'] || in_array($filter->getId(), $allowedFilters);
    }

    /**
     * A filter id is a column, or a relation and a column ("owner.name"). Anything
     * else - spaces, quotes, parentheses, operators - is an attempt to smuggle SQL.
     */
    private function isSafeIdentifier(string $id): bool
    {
        return preg_match('/^[A-Za-z_][A-Za-z0-9_]*(\.[A-Za-z_][A-Za-z0-9_]*)?$/', $id) === 1;
    }

    protected function isValidSearchFunction(Filter $filter): bool
    {
        $searchFunction = $filter->getFn();

        return isset($searchFunction) && in_array($searchFunction, SearchType::values());
    }

    protected function isValidDataType(Filter $filter): int
    {
        if (! property_exists($filter, 'datatype')) {
            return -1;
        }

        return $filter->getDatatype() && in_array($filter->getDatatype(), DataType::values()) ? 1 : 0;
    }
}
