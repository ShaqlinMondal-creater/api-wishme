<?php

namespace App\Services;

use App\Models\OccasionsModel;
use App\Models\ProjectsModel;
use App\Models\TemplatesModel;
use App\Models\UploadsModel;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

class StoreUpload
{
    public function forOccasion(UploadedFile $file, int $userId, OccasionsModel $occasion): UploadsModel
    {
        $upload = $this->store($file, $userId, null, null, 'occasions/'.$occasion->id, $occasion->id);
        $occasion->thumbnail_id = $upload->id;
        $occasion->save();

        return $upload;
    }

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
        ?int $occasionId = null,
    ): UploadsModel {
        if ($templateId === null && $projectId === null && $occasionId === null) {
            throw new InvalidArgumentException('An upload must belong to a template, a project, or an occasion.');
        }

        $extension = strtolower($file->getClientOriginalExtension());
        $filename = Str::random(40).($extension !== '' ? '.'.$extension : '');
        $path = $folder.'/'.$filename;
        $absolute = public_path('uploads'.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $path));
        $directory = dirname($absolute);

        if (! is_dir($directory) && ! mkdir($directory, 0755, true) && ! is_dir($directory)) {
            throw new RuntimeException('Could not create the uploads folder.');
        }

        $source = $file->getRealPath() ?: $file->getPathname();
        $copied = copy($source, $absolute);

        if (! $copied) {
            throw new RuntimeException('The file could not be saved.');
        }

        $mime = $this->detectMime($file, $extension);

        return UploadsModel::query()->create([
            'user_id' => $userId,
            'template_id' => $templateId,
            'project_id' => $projectId,
            'occasion_id' => $occasionId,
            'kind' => UploadsModel::kindFromExtension($extension),
            'disk' => 'uploads',
            'path' => $path,
            'url' => $this->publicUrl($path),
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

    private function publicUrl(string $path): string
    {
        return rtrim((string) config('filesystems.disks.uploads.url'), '/').'/'.ltrim($path, '/');
    }
}
