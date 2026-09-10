<?php

namespace App\Http\Requests;

use App\Models\UploadsModel;

class StoreUploadRequest extends ApiFormRequest
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
            'file' => [
                'required',
                'file',
                'max:20480',
                'mimetypes:'.implode(',', UploadsModel::MIMES),
            ],
        ];
    }
}
