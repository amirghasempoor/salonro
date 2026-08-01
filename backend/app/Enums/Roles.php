<?php

namespace App\Enums;

enum Roles: string
{
    case Admin = 'admin';
    case Expert = 'expert';
    case Manager = 'manager';
    case User = 'user';

    public static function labels(int $id): string
    {
        $map = [
            1 => 'admin',
            2 => 'expert',
            3 => 'manager',
            4 => 'user',
        ];
        return $map[$id];
    }
}
