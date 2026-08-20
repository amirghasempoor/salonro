<?php

namespace App\Enums;

enum DiscountAmountType: string
{
    case Percentage = 'percentage';
    case Fixed = 'fixed';
}
