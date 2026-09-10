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

        $path = $file->store($folder, 'uploads');
        $mime = $file->getMimeType() ?: 'application/octet-stream';

        return UploadsModel::query()->create([
            'user_id' => $userId,
            'template_id' => $templateId,
            'project_id' => $projectId,
            'kind' => UploadsModel::kindFromMime($mime),
            'disk' => 'uploads',
            'path' => $path,
            'url' => Storage::disk('uploads')->url($path),
            'original_name' => Str::limit($file->getClientOriginalName(), 255, ''),
            'mime' => $mime,
            'size' => $file->getSize(),
        ]);
    }
}
