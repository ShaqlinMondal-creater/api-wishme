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
            'content' => ['sometimes', 'array'],
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

        $rooms = [];

        if (isset($content['rooms']) && is_array($content['rooms'])) {
            $rooms = $content['rooms'];
        } else {
            foreach (array_keys(TemplatesModel::CONTENT_ROOMS) as $room) {
                if (array_key_exists($room, $content)) {
                    $rooms[$room] = $content[$room];
                }
            }
        }

        $labels = $template->contentRoomLabels();

        foreach ($rooms as $room => $value) {
            if (! is_string($room) || ! isset(TemplatesModel::CONTENT_ROOMS[$room])) {
                $validator->errors()->add(
                    is_string($room) ? "content.rooms.{$room}" : 'content.rooms',
                    'This room is not valid for the template.',
                );

                continue;
            }

            if (! $template->allowsContentRoom($room)) {
                $validator->errors()->add(
                    "content.rooms.{$room}",
                    ($labels[$room] ?? $room).' is not enabled on this template.',
                );
            }
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
