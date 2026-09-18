<?php

namespace Expert\Domain\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

/**
 * Base for the Expert module's domain exceptions. Renders itself as the
 * app's standard {type, message} 422 error contract, so Actions can throw
 * instead of the controller doing try/catch.
 */
abstract class ExpertException extends Exception
{
    public function __construct(string $message, private readonly int $statusCode = 422)
    {
        parent::__construct($message);
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'type' => 'logical_exception',
            'message' => $this->getMessage(),
        ], $this->statusCode);
    }
}
