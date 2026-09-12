<?php

namespace App\Enums;

enum CouponAppliesTo: string
{
    case Template = 'template';
    case Subscription = 'subscription';
    case Both = 'both';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
