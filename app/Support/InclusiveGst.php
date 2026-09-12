<?php

namespace App\Support;

final class InclusiveGst
{
    public const PERCENT = 18;

    /**
     * Split a tax-inclusive rupee total into exclusive price + GST.
     *
     * @return array{base: float, tax: float, total: float, percent: int}
     */
    public static function split(int $totalRupees): array
    {
        $totalPaise = max(0, $totalRupees * 100);
        $basePaise = intdiv($totalPaise * 100, 100 + self::PERCENT);
        $taxPaise = $totalPaise - $basePaise;

        return [
            'base' => $basePaise / 100,
            'tax' => $taxPaise / 100,
            'total' => $totalPaise / 100,
            'percent' => self::PERCENT,
        ];
    }
}
