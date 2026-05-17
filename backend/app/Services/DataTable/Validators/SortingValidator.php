<?php

namespace App\Services\DataTable\Validators;

use App\Services\DataTable\Exceptions\InvalidSortingException;
use App\Services\DataTable\Sort\Sort;

class SortingValidator
{
    private static $instance;

    private function __construct()
    {
    }

    public static function getInstance(): SortingValidator
    {
        if (!isset(self::$instance)) {
            self::$instance = new static();
        }

        return self::$instance;
    }

    public function isValid(Sort $sorting, array $allowedSortings): bool
    {
        if (!$this->isAllowed($sorting, $allowedSortings)) {
            $sortId = $sorting->getId();
            throw new InvalidSortingException($sortId, "sorting field `$sortId` is not allowed.");
        }

        return true;
    }

    protected function isAllowed(Sort $sorting, array $allowedSortings): bool
    {
        return $allowedSortings == ['*'] || in_array($sorting->getId(), $allowedSortings);
    }
}
