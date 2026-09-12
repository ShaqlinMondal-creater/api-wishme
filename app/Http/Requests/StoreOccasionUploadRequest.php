<?php

namespace App\Http\Requests;

use App\Models\UploadsModel;
use Illuminate\Http\UploadedFile;

class StoreOccasionUploadRequest extends ApiFormRequest
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
                'max:10240',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! $value instanceof UploadedFile) {
                        $fail('Please choose an image.');

                        return;
                    }

                    $extension = strtolower($value->getClientOriginalExtension());

                    if (! in_array($extension, UploadsModel::IMAGE_EXTENSIONS, true)) {
                        $fail('Please choose a JPG, PNG, WEBP, or GIF image.');
                    }
                },
            ],
        ];
    }
}
