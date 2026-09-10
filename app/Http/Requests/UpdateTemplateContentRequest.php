<?php

namespace App\Http\Requests;

class UpdateTemplateContentRequest extends ApiFormRequest
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
            'content' => ['required', 'array'],
            'content.gate' => ['sometimes', 'array'],
            'content.gate.cover' => ['sometimes', 'nullable', 'string', 'max:2048'],
            'content.gate.quote' => ['sometimes', 'nullable', 'string', 'max:500'],
            'content.gate.recipient' => ['sometimes', 'nullable', 'string', 'max:120'],
            'content.gate.from' => ['sometimes', 'nullable', 'string', 'max:120'],
            'content.gate.occasion' => ['sometimes', 'nullable', 'string', 'max:80'],
            'content.gate.body' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'content.gate.cta' => ['sometimes', 'nullable', 'string', 'max:80'],
            'content.gate.footer' => ['sometimes', 'nullable', 'string', 'max:240'],
            'content.hub' => ['sometimes', 'array'],
            'content.hub.cover' => ['sometimes', 'nullable', 'string', 'max:2048'],
            'content.hub.intro' => ['sometimes', 'nullable', 'string', 'max:500'],
            'content.rooms' => ['sometimes', 'array'],
            'content.rooms.letter' => ['sometimes', 'array'],
            'content.rooms.stories' => ['sometimes', 'array'],
            'content.rooms.moments' => ['sometimes', 'array'],
            'content.rooms.privacy' => ['sometimes', 'array'],
            'content.rooms.gifts' => ['sometimes', 'array'],
        ];
    }
}
