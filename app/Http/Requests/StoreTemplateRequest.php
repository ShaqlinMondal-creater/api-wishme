<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesTemplatePayload;

class StoreTemplateRequest extends ApiFormRequest
{
    use ValidatesTemplatePayload;

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return $this->templateFieldRules();
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->templateMessages();
    }
}
