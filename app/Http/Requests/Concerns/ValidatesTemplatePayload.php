<?php

namespace App\Http\Requests\Concerns;

use Illuminate\Validation\Rule;

trait ValidatesTemplatePayload
{
    /**
     * @return array<string, list<mixed>>
     */
    protected function templateFieldRules(?int $ignoreId = null): array
    {
        return [
            'slug' => [
                'required',
                'string',
                'max:80',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('t_templates', 'slug')->ignore($ignoreId),
            ],
            'name' => ['required', 'string', 'max:120'],
            'description' => ['required', 'string', 'max:2000'],
            'occasion_id' => ['required', 'integer', Rule::exists('t_occasion', 'id')],
            'price' => ['required', 'integer', 'min:0', 'max:999999'],
            'has_letter' => ['required', 'boolean'],
            'has_stories' => ['required', 'boolean'],
            'has_moments' => ['required', 'boolean'],
            'has_privacy' => ['required', 'boolean'],
            'has_surprise_gift' => ['required', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function templateMessages(): array
    {
        return [
            'slug.regex' => 'Use a lowercase slug like midnight-toast.',
            'slug.unique' => 'This slug is already used by another template.',
            'occasion_id.exists' => 'Choose a valid occasion.',
            'price.min' => 'Price cannot be negative.',
        ];
    }
}
