<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesCouponPayload;

class StoreCouponRequest extends ApiFormRequest
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
        return $this->couponFieldRules();
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->couponMessages();
    }
}
