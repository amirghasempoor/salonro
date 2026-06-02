<?php

namespace App\Enums;

enum Roles: string
{
    case Admin = 'admin';
    case User = 'user';
    case Expert = 'expert';
    case Manager = 'manager';
}
