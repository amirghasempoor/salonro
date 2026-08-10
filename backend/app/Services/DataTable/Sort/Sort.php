<?php

namespace App\Services\DataTable\Sort;

use App\Services\DataTable\Exceptions\InvalidSortingException;
use App\Services\DataTable\Validators\SortingValidator;

class Sort
{
    private static SortingValidator $sortingValidator;

    /**
     * @throws InvalidSortingException
     */
    public function __construct(
        private string $id,
        private bool $desc,
        private array $allowedSortings,
    ) {
        self::$sortingValidator = SortingValidator::getInstance();
        self::$sortingValidator->isValid($this, $this->allowedSortings);
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getDirection(): string
    {
        return $this->desc === true ? 'desc' : 'asc';
    }
}
