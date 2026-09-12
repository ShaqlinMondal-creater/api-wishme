<?php

namespace App\Enums;

enum CouponDiscountType: string
{
    case Flat = 'flat';
    case Percent = 'percent';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
