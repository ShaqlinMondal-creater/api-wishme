<?php

namespace App\Http\Requests\Concerns;

use App\Models\PurchasesModel;
use App\Models\TemplatesModel;
use Illuminate\Contracts\Validation\Validator;

trait ValidatesProjectContent
{
    /**
     * @return array<string, list<mixed>>
     */
    protected function contentFieldRules(): array
    {
        return [
            'content' => ['required', 'array'],
            'content.letter' => ['sometimes', 'array'],
            'content.letter.greeting' => ['sometimes', 'nullable', 'string', 'max:160'],
            'content.letter.body' => ['sometimes', 'nullable'],
            'content.letter.signoff' => ['sometimes', 'nullable', 'string', 'max:160'],
            'content.letter.date' => ['sometimes', 'nullable', 'string', 'max:80'],
            'content.stories' => ['sometimes', 'array'],
            'content.stories.*' => ['array'],
            'content.moments' => ['sometimes', 'array'],
            'content.moments.*' => ['array'],
            'content.moments.*.title' => ['sometimes', 'nullable', 'string', 'max:160'],
            'content.moments.*.body' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'content.moments.*.image' => ['sometimes', 'nullable', 'string', 'max:500'],
            'content.moments.*.time' => ['sometimes', 'nullable', 'string', 'max:40'],
            'content.privacy' => ['sometimes', 'array'],
            'content.gifts' => ['sometimes', 'array', 'max:9'],
            'content.gifts.*' => ['array'],
            'content.gifts.*.id' => ['sometimes', 'nullable', 'string', 'max:40'],
            'content.gifts.*.emoji' => ['sometimes', 'nullable', 'string', 'max:16'],
            'content.gifts.*.title' => ['sometimes', 'nullable', 'string', 'max:160'],
            'content.gifts.*.body' => ['sometimes', 'nullable', 'string', 'max:500'],
        ];
    }

    protected function assertContentMatchesTemplate(Validator $validator, TemplatesModel $template): void
    {
        $content = $this->input('content');

        if (! is_array($content)) {
            return;
        }

        if ($content !== [] && array_is_list($content)) {
            $validator->errors()->add('content', 'Content must be an object of rooms.');

            return;
        }

        $labels = $template->contentRoomLabels();

        foreach ($content as $room => $value) {
            if (! is_string($room) || ! isset(TemplatesModel::CONTENT_ROOMS[$room])) {
                $validator->errors()->add(
                    is_string($room) ? "content.{$room}" : 'content',
                    'This room is not valid for the template.',
                );

                continue;
            }

            if (! $template->allowsContentRoom($room)) {
                $validator->errors()->add(
                    "content.{$room}",
                    ($labels[$room] ?? $room).' is not enabled on this template.',
                );
            }
        }

        $letterBody = data_get($content, 'letter.body');

        if (is_array($letterBody)) {
            foreach ($letterBody as $index => $line) {
                if (! is_string($line)) {
                    $validator->errors()->add("content.letter.body.{$index}", 'Each letter line must be text.');
                }
            }
        } elseif ($letterBody !== null && ! is_string($letterBody)) {
            $validator->errors()->add('content.letter.body', 'Letter body must be text or a list of text.');
        }
    }

    protected function findOwnedPaidPurchase(int $purchaseId, int $userId): ?PurchasesModel
    {
        return PurchasesModel::query()
            ->with('template')
            ->where('id', $purchaseId)
            ->where('user_id', $userId)
            ->first();
    }
}
