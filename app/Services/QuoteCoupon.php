<?php

namespace App\Services;

use App\Enums\CouponAppliesTo;
use App\Enums\CouponDiscountType;
use App\Models\CouponsModel;
use App\Models\TemplatesModel;
use App\Support\InclusiveGst;
use InvalidArgumentException;

class QuoteCoupon
{
    /**
     * @return array<string, mixed>
     */
    public function forTemplate(string $code, int $userId, string $templateKey): array
    {
        $template = $this->findPublicTemplate($templateKey);

        if ($template === null) {
            throw new InvalidArgumentException('Template not found.');
        }

        $coupon = CouponsModel::query()
            ->where('code', strtoupper(trim($code)))
            ->first();

        if ($coupon === null) {
            throw new InvalidArgumentException('This coupon code is not valid.');
        }

        $this->assertUsable($coupon, $userId, CouponAppliesTo::Template);

        $original = (int) $template->price;

        if ($original <= 0) {
            throw new InvalidArgumentException('This template is free, so a coupon is not needed.');
        }

        $amountOff = $this->amountOff($coupon, $original);
        $payable = max(0, $original - $amountOff);
        $gst = InclusiveGst::split($payable);

        return [
            'coupon' => $coupon->toPublicApiArray(),
            'original_price' => $original,
            'amount_off' => $amountOff,
            'payable' => $payable,
            'base' => $gst['base'],
            'tax' => $gst['tax'],
            'tax_percent' => $gst['percent'],
        ];
    }

    private function assertUsable(CouponsModel $coupon, int $userId, CouponAppliesTo $context): void
    {
        if (! $coupon->is_active) {
            throw new InvalidArgumentException('This coupon is not active.');
        }

        if ($coupon->starts_at?->isFuture()) {
            throw new InvalidArgumentException('This coupon is not valid yet.');
        }

        if ($coupon->ends_at?->isPast()) {
            throw new InvalidArgumentException('This coupon has expired.');
        }

        $appliesTo = $coupon->applies_to instanceof CouponAppliesTo
            ? $coupon->applies_to
            : CouponAppliesTo::tryFrom((string) $coupon->applies_to);

        if ($appliesTo !== CouponAppliesTo::Both && $appliesTo !== $context) {
            throw new InvalidArgumentException(
                $context === CouponAppliesTo::Template
                    ? 'This coupon cannot be used on templates.'
                    : 'This coupon cannot be used on subscriptions.',
            );
        }

        if ($coupon->max_uses !== null) {
            $used = max((int) $coupon->used_count, $coupon->uses()->count());

            if ($used >= (int) $coupon->max_uses) {
                throw new InvalidArgumentException('This coupon has reached its usage limit.');
            }
        }

        $perUser = max(1, (int) $coupon->max_uses_per_user);
        $userUses = $coupon->uses()->where('user_id', $userId)->count();

        if ($userUses >= $perUser) {
            throw new InvalidArgumentException('You have already used this coupon.');
        }
    }

    private function amountOff(CouponsModel $coupon, int $original): int
    {
        $type = $coupon->discount_type instanceof CouponDiscountType
            ? $coupon->discount_type
            : CouponDiscountType::tryFrom((string) $coupon->discount_type);

        if ($type === CouponDiscountType::Percent) {
            $off = intdiv($original * (int) $coupon->amount, 100);
        } else {
            $off = (int) $coupon->amount;
        }

        return min($original, max(0, $off));
    }

    private function findPublicTemplate(string $id): ?TemplatesModel
    {
        $query = TemplatesModel::query()->where('is_active', true);

        if (ctype_digit($id)) {
            return $query->where('id', (int) $id)->first();
        }

        return $query->where('slug', $id)->first();
    }
}
