<?php

namespace App\Models;

use App\Enums\CouponAppliesTo;
use App\Enums\CouponDiscountType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'code',
    'title',
    'discount_type',
    'amount',
    'applies_to',
    'is_active',
    'starts_at',
    'ends_at',
    'max_uses',
    'max_uses_per_user',
])]
class CouponsModel extends Model
{
    protected $table = 't_coupons';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'discount_type' => CouponDiscountType::class,
            'applies_to' => CouponAppliesTo::class,
            'amount' => 'integer',
            'is_active' => 'boolean',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'max_uses' => 'integer',
            'max_uses_per_user' => 'integer',
            'used_count' => 'integer',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toApiArray(): array
    {
        $discountType = $this->discount_type instanceof CouponDiscountType
            ? $this->discount_type
            : CouponDiscountType::tryFrom((string) $this->discount_type);
        $appliesTo = $this->applies_to instanceof CouponAppliesTo
            ? $this->applies_to
            : CouponAppliesTo::tryFrom((string) $this->applies_to);

        return [
            'id' => $this->id,
            'code' => $this->code,
            'title' => $this->title,
            'discount_type' => $discountType?->value ?? $this->discount_type,
            'amount' => (int) $this->amount,
            'applies_to' => $appliesTo?->value ?? $this->applies_to,
            'is_active' => (bool) $this->is_active,
            'starts_at' => $this->starts_at?->toIso8601String(),
            'ends_at' => $this->ends_at?->toIso8601String(),
            'max_uses' => $this->max_uses,
            'max_uses_per_user' => (int) $this->max_uses_per_user,
            'used_count' => (int) $this->used_count,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
