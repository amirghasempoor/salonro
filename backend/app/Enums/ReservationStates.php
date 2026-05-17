<?php

namespace App\Enums;

enum ReservationStates: int
{
    case Reserve = 1;
    case Cancel = 2;
    case Done = 3;

    public function label(): string
    {
        return match ($this) {
            self::Reserve => 'رزرو',
            self::Cancel => 'لغو',
            self::Done => 'انجام شده'
        };
    }
}
