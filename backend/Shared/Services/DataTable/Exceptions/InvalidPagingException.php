<?php

namespace Shared\Services\DataTable\Exceptions;

use Exception;

/**
 * The paging, filter or sorting input is not shaped the way a table request must be.
 */
class InvalidPagingException extends Exception implements InvalidParameterInterface
{
    public function __construct(string $message)
    {
        parent::__construct($message, 400);
    }
}
