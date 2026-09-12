<?php

namespace App\Http\Controllers;

use App\Enums\CouponAppliesTo;
use App\Http\Requests\StoreCouponRequest;
use App\Http\Requests\UpdateCouponRequest;
use App\Models\CouponsModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CouponsController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'applies_to' => ['nullable', Rule::in(CouponAppliesTo::values())],
            'status' => ['nullable', 'in:active,inactive'],
        ]);

        $coupons = CouponsModel::query()
            ->when(
                $request->filled('applies_to'),
                fn ($query) => $query->where('applies_to', $request->string('applies_to')->toString()),
            )
            ->when(
                $request->input('status') === 'active',
                fn ($query) => $query->where('is_active', true),
            )
            ->when(
                $request->input('status') === 'inactive',
                fn ($query) => $query->where('is_active', false),
            )
            ->orderByDesc('id')
            ->get()
            ->map(fn (CouponsModel $coupon) => $coupon->toApiArray())
            ->values();

        return $this->success('Coupons fetched successfully.', [
            'coupons' => $coupons,
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $coupon = CouponsModel::query()->find($id);

        if ($coupon === null) {
            return $this->error('Coupon not found.', 404);
        }

        return $this->success('Coupon fetched successfully.', [
            'coupon' => $coupon->toApiArray(),
        ]);
    }

    public function store(StoreCouponRequest $request): JsonResponse
    {
        $coupon = CouponsModel::query()->create($this->payload($request->safe()->all()));

        return $this->success('Coupon created successfully.', [
            'coupon' => $coupon->fresh()?->toApiArray(),
        ], 201);
    }

    public function update(UpdateCouponRequest $request, int $id): JsonResponse
    {
        $coupon = CouponsModel::query()->find($id);

        if ($coupon === null) {
            return $this->error('Coupon not found.', 404);
        }

        $coupon->fill($this->payload($request->safe()->all()));
        $coupon->save();

        return $this->success('Coupon updated successfully.', [
            'coupon' => $coupon->fresh()?->toApiArray(),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $coupon = CouponsModel::query()->find($id);

        if ($coupon === null) {
            return $this->error('Coupon not found.', 404);
        }

        $coupon->delete();

        return $this->success('Coupon deleted successfully.');
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    private function payload(array $input): array
    {
        return [
            'code' => $input['code'],
            'title' => $input['title'],
            'discount_type' => $input['discount_type'],
            'amount' => $input['amount'],
            'applies_to' => $input['applies_to'],
            'is_active' => array_key_exists('is_active', $input) ? (bool) $input['is_active'] : true,
            'starts_at' => $input['starts_at'] ?? null,
            'ends_at' => $input['ends_at'] ?? null,
            'max_uses' => $input['max_uses'] ?? null,
            'max_uses_per_user' => $input['max_uses_per_user'],
        ];
    }
}
