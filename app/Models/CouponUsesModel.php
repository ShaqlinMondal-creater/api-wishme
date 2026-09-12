<?php

namespace App\Models;

use App\Enums\CouponUseAppliedTo;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'coupon_id',
    'user_id',
    'applied_to',
    'amount_off',
    'original_price',
    'purchase_id',
    'starts_at',
    'ends_at',
])]
class CouponUsesModel extends Model
{
    protected $table = 't_coupon_uses';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'coupon_id' => 'integer',
            'user_id' => 'integer',
            'applied_to' => CouponUseAppliedTo::class,
            'amount_off' => 'integer',
            'original_price' => 'integer',
            'purchase_id' => 'integer',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(CouponsModel::class, 'coupon_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(UsersModel::class, 'user_id');
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(PurchasesModel::class, 'purchase_id');
    }

    /**
     * @return array<string, mixed>
     */
    public function toApiArray(): array
    {
        $appliedTo = $this->applied_to instanceof CouponUseAppliedTo
            ? $this->applied_to
            : CouponUseAppliedTo::tryFrom((string) $this->applied_to);
        $coupon = $this->relationLoaded('coupon') ? $this->coupon : null;
        $user = $this->relationLoaded('user') ? $this->user : null;

        return [
            'id' => $this->id,
            'coupon_id' => $this->coupon_id,
            'coupon_code' => $coupon?->code,
            'coupon_title' => $coupon?->title,
            'user_id' => $this->user_id,
            'user_name' => $user?->name,
            'user_email' => $user?->email,
            'applied_to' => $appliedTo?->value ?? $this->applied_to,
            'amount_off' => (int) $this->amount_off,
            'original_price' => $this->original_price,
            'purchase_id' => $this->purchase_id,
            'starts_at' => $this->starts_at?->toIso8601String(),
            'ends_at' => $this->ends_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
