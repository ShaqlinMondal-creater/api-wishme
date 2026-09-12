<?php

namespace App\Http\Requests\Concerns;

use App\Enums\CouponAppliesTo;
use App\Enums\CouponDiscountType;
use Illuminate\Validation\Rule;

trait ValidatesCouponPayload
{
    protected function prepareForValidation(): void
    {
        if ($this->has('code')) {
            $this->merge([
                'code' => strtoupper(trim((string) $this->input('code'))),
            ]);
        }

        foreach (['starts_at', 'ends_at', 'max_uses'] as $field) {
            if ($this->exists($field) && $this->input($field) === '') {
                $this->merge([$field => null]);
            }
        }
    }

    /**
     * @return array<string, list<mixed>>
     */
    protected function couponFieldRules(?int $ignoreId = null): array
    {
        $isPercent = $this->input('discount_type') === CouponDiscountType::Percent->value;

        return [
            'code' => [
                'required',
                'string',
                'max:40',
                'regex:/^[A-Z0-9]+(?:-[A-Z0-9]+)*$/',
                Rule::unique('t_coupons', 'code')->ignore($ignoreId),
            ],
            'title' => ['required', 'string', 'max:120'],
            'discount_type' => ['required', Rule::in(CouponDiscountType::values())],
            'amount' => [
                'required',
                'integer',
                'min:1',
                $isPercent ? 'max:100' : 'max:999999',
            ],
            'applies_to' => ['required', Rule::in(CouponAppliesTo::values())],
            'is_active' => ['sometimes', 'boolean'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => [
                'nullable',
                'date',
                Rule::when($this->filled('starts_at'), ['after_or_equal:starts_at']),
            ],
            'max_uses' => ['nullable', 'integer', 'min:1', 'max:999999'],
            'max_uses_per_user' => ['required', 'integer', 'min:1', 'max:999999'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function couponMessages(): array
    {
        return [
            'code.regex' => 'Use uppercase letters, numbers, and hyphens, like WISHME50.',
            'code.unique' => 'This code is already used by another coupon.',
            'discount_type.in' => 'Discount must be flat or percent.',
            'applies_to.in' => 'Applies to must be template, subscription, or both.',
            'amount.max' => $this->input('discount_type') === CouponDiscountType::Percent->value
                ? 'Percent off cannot be more than 100.'
                : 'Flat off cannot be more than ₹999999.',
            'ends_at.after_or_equal' => 'End date cannot be before the start date.',
        ];
    }
}
