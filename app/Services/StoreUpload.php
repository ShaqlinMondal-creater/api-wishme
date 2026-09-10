<?php

namespace App\Services;

use App\Models\ProjectsModel;
use App\Models\TemplatesModel;
use App\Models\UploadsModel;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;

class StoreUpload
{
    public function forTemplate(UploadedFile $file, int $userId, TemplatesModel $template): UploadsModel
    {
        return $this->store($file, $userId, $template->id, null, 'templates/'.$template->id);
    }

    public function forProject(UploadedFile $file, int $userId, ProjectsModel $project): UploadsModel
    {
        return $this->store($file, $userId, $project->template_id, $project->id, 'projects/'.$project->id);
    }

    private function store(
        UploadedFile $file,
        int $userId,
        ?int $templateId,
        ?int $projectId,
        string $folder,
    ): UploadsModel {
        if ($templateId === null && $projectId === null) {
            throw new InvalidArgumentException('An upload must belong to a template or a project.');
        }

        $extension = strtolower($file->getClientOriginalExtension());
        $filename = Str::random(40).($extension !== '' ? '.'.$extension : '');
        $path = $folder.'/'.$filename;
        $contents = file_get_contents($file->getRealPath() ?: $file->getPathname());

        if ($contents === false) {
            throw new InvalidArgumentException('The file could not be read.');
        }

        Storage::disk('uploads')->put($path, $contents);
        $mime = $this->detectMime($file, $extension);

        return UploadsModel::query()->create([
            'user_id' => $userId,
            'template_id' => $templateId,
            'project_id' => $projectId,
            'kind' => UploadsModel::kindFromExtension($extension),
            'disk' => 'uploads',
            'path' => $path,
            'url' => Storage::disk('uploads')->url($path),
            'original_name' => Str::limit($file->getClientOriginalName(), 255, ''),
            'mime' => $mime,
            'size' => $file->getSize(),
        ]);
    }

    private function detectMime(UploadedFile $file, string $extension): string
    {
        $fromClient = $file->getClientMimeType();

        if (is_string($fromClient) && $fromClient !== '' && $fromClient !== 'application/octet-stream') {
            return $fromClient;
        }

        return UploadsModel::MIME_BY_EXTENSION[$extension] ?? 'application/octet-stream';
    }
}
