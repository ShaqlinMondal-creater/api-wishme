<?php

namespace App\Http\Requests;

use App\Enums\ProjectStatus;
use App\Http\Requests\Concerns\ValidatesProjectContent;
use App\Models\TemplatesModel;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Rule;

class StoreProjectRequest extends ApiFormRequest
{
    use ValidatesProjectContent;

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
            'recipient_name' => ['required', 'string', 'max:120'],
            'from_name' => ['required', 'string', 'max:120'],
            'template_id' => ['required', 'integer', 'exists:templates,id'],
            'purchase_id' => [
                'nullable',
                'integer',
                'exists:purchases,id',
                Rule::unique('projects', 'purchase_id'),
            ],
            'status' => ['sometimes', Rule::in([ProjectStatus::Draft->value])],
            ...$this->contentFieldRules(),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'purchase_id.unique' => 'A project already exists for this purchase.',
            'status.in' => 'New projects must stay as drafts until publishing is available.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $user = $this->user();

            if ($user === null) {
                $validator->errors()->add('template_id', 'You must be signed in to create a project.');

                return;
            }

            $template = TemplatesModel::query()->find($this->integer('template_id'));

            if ($template === null || ! $template->is_active) {
                $validator->errors()->add('template_id', 'This template is not available.');

                return;
            }

            $purchaseId = $this->input('purchase_id');

            if ($purchaseId !== null && $purchaseId !== '') {
                $purchase = $this->findOwnedPaidPurchase(
                    (int) $purchaseId,
                    (int) $user->id,
                );

                if ($purchase === null) {
                    $validator->errors()->add('purchase_id', 'This purchase was not found for your account.');

                    return;
                }

                if (! $purchase->isPaid()) {
                    $validator->errors()->add('purchase_id', 'This template has not been paid for.');
                }

                if ((int) $purchase->template_id !== $this->integer('template_id')) {
                    $validator->errors()->add('template_id', 'The template does not match this purchase.');
                }
            }

            if ($this->exists('content')) {
                $this->assertContentMatchesTemplate($validator, $template);
            }
        });
    }
}
