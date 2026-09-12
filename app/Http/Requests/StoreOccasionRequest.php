<?php

namespace App\Http\Requests;

use App\Enums\OccasionType;
use Illuminate\Validation\Rule;

class StoreOccasionRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:120'],
            'description' => ['required', 'string', 'max:2000'],
            'type' => ['required', Rule::in(OccasionType::values())],
            'thumbnail_id' => [
                'nullable',
                'integer',
                Rule::exists('t_uploads', 'id'),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'type.in' => 'Type must be birthday, anniversary, bhai-phota, raksha-bandhan, proposal, or dating.',
        ];
    }
}
