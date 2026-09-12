<?php

namespace App\Http\Controllers;

use App\Enums\CouponAppliesTo;
use App\Enums\CouponUseAppliedTo;
use App\Http\Requests\StoreCouponRequest;
use App\Http\Requests\UpdateCouponRequest;
use App\Models\CouponUsesModel;
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

    public function uses(Request $request): JsonResponse
    {
        $request->validate([
            'coupon_id' => ['nullable', 'integer'],
            'applied_to' => ['nullable', Rule::in(CouponUseAppliedTo::values())],
            'search' => ['nullable', 'string', 'max:120'],
            'used_from' => ['nullable', 'date'],
            'used_to' => ['nullable', 'date', 'after_or_equal:used_from'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
            'offset' => ['nullable', 'integer', 'min:0'],
        ]);

        $limit = $request->integer('limit', 25);
        $offset = $request->integer('offset', 0);
        $search = trim($request->string('search')->toString());

        $query = CouponUsesModel::query()
            ->with(['coupon', 'user'])
            ->when(
                $request->filled('coupon_id'),
                fn ($query) => $query->where('coupon_id', $request->integer('coupon_id')),
            )
            ->when(
                $request->filled('applied_to'),
                fn ($query) => $query->where('applied_to', $request->string('applied_to')->toString()),
            )
            ->when(
                $search !== '',
                function ($query) use ($search) {
                    $term = '%'.addcslashes($search, '%_\\').'%';

                    $query->where(function ($query) use ($term) {
                        $query
                            ->whereHas('user', function ($query) use ($term) {
                                $query
                                    ->where('name', 'like', $term)
                                    ->orWhere('email', 'like', $term);
                            })
                            ->orWhereHas('coupon', function ($query) use ($term) {
                                $query
                                    ->where('code', 'like', $term)
                                    ->orWhere('title', 'like', $term);
                            });
                    });
                },
            )
            ->when(
                $request->filled('used_from'),
                fn ($query) => $query->whereDate('created_at', '>=', $request->date('used_from')->toDateString()),
            )
            ->when(
                $request->filled('used_to'),
                fn ($query) => $query->whereDate('created_at', '<=', $request->date('used_to')->toDateString()),
            );

        $total = (clone $query)->count();
        $uses = $query
            ->orderByDesc('id')
            ->offset($offset)
            ->limit($limit)
            ->get()
            ->map(fn (CouponUsesModel $use) => $use->toApiArray())
            ->values();

        return $this->success('Coupon usage fetched successfully.', [
            'uses' => $uses,
            'total' => $total,
            'limit' => $limit,
            'offset' => $offset,
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

        if ($coupon->uses()->exists()) {
            return $this->error('This coupon cannot be deleted because it has been used.', 422);
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
