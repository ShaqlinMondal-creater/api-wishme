<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesCouponPayload;

class UpdateCouponRequest extends ApiFormRequest
{
    use ValidatesCouponPayload;

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return $this->couponFieldRules((int) $this->route('id'));
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->couponMessages();
    }
}
