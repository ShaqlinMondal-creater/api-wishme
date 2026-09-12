<?php

namespace App\Enums;

enum CouponUseAppliedTo: string
{
    case Template = 'template';
    case Subscription = 'subscription';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
