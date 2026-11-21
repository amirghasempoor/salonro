<?php

namespace Shared\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

/**
 * A reservation that is already finished, cancelled or in the past cannot be
 * cancelled or moved any more. Renders itself as the app's standard 422.
 */
class ReservationNotChangeableException extends Exception
{
    public static function forCancel(): self
    {
        return new self(__('messages.reservation_cannot_be_cancelled'));
    }

    public static function forReschedule(): self
    {
        return new self(__('messages.reservation_cannot_be_rescheduled'));
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'type' => 'logical_exception',
            'message' => $this->getMessage(),
        ], 422);
    }
}
