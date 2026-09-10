<?php

namespace App\Http\Requests;

use App\Enums\ProjectStatus;
use App\Http\Requests\Concerns\ValidatesProjectContent;
use App\Models\ProjectsModel;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Rule;

class UpdateProjectRequest extends ApiFormRequest
{
    use ValidatesProjectContent;

    private ?ProjectsModel $resolvedProject = null;

    private bool $projectResolved = false;

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
            'title' => ['sometimes', 'string', 'max:120'],
            'recipient_name' => ['sometimes', 'string', 'max:120'],
            'from_name' => ['sometimes', 'string', 'max:120'],
            'template_id' => ['prohibited'],
            'purchase_id' => ['prohibited'],
            'status' => ['sometimes', Rule::in([ProjectStatus::Draft->value])],
            ...$this->contentFieldRules(),
            'content' => ['sometimes', 'array'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'template_id.prohibited' => 'You cannot change the template after the project is created.',
            'purchase_id.prohibited' => 'You cannot change the purchase after the project is created.',
            'status.in' => 'Publishing is not available yet. Projects must stay as drafts.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty() || ! $this->exists('content')) {
                return;
            }

            $project = $this->project();

            if ($project === null || $project->template === null) {
                return;
            }

            $this->assertContentMatchesTemplate($validator, $project->template);
        });
    }

    public function project(): ?ProjectsModel
    {
        if ($this->projectResolved) {
            return $this->resolvedProject;
        }

        $this->projectResolved = true;
        $user = $this->user();

        if ($user === null) {
            return null;
        }

        $this->resolvedProject = ProjectsModel::query()
            ->with('template')
            ->where('id', (int) $this->route('id'))
            ->where('user_id', $user->id)
            ->first();

        return $this->resolvedProject;
    }
}
