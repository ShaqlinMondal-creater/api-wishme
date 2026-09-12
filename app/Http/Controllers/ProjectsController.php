<?php

namespace App\Http\Controllers;

use App\Enums\ProjectStatus;
use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\StoreUploadRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Models\ProjectsModel;
use App\Models\TemplatesModel;
use App\Models\UploadsModel;
use App\Services\StoreUpload;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProjectsController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $projects = ProjectsModel::query()
            ->with(['template.occasion'])
            ->where('user_id', $request->user()?->id)
            ->orderByDesc('id')
            ->get()
            ->map(fn (ProjectsModel $project) => $project->toApiArray())
            ->values();

        return $this->success('Projects fetched successfully.', [
            'projects' => $projects,
        ]);
    }

    public function store(StoreProjectRequest $request): JsonResponse
    {
        $data = $request->safe()->only([
            'title',
            'recipient_name',
            'from_name',
            'template_id',
            'purchase_id',
            'content',
        ]);

        $template = TemplatesModel::query()->find($data['template_id']);

        $project = ProjectsModel::query()->create([
            ...$data,
            'user_id' => $request->user()->id,
            'purchase_id' => $data['purchase_id'] ?? null,
            'content' => $data['content'] ?? $template?->content ?? [],
            'status' => ProjectStatus::Draft,
        ]);

        return $this->success('Project created successfully.', [
            'project' => $project->load(['template.occasion'])->toApiArray(),
        ], 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $project = $this->ownedProject($request, $id);

        if ($project === null) {
            return $this->error('Project not found.', 404);
        }

        return $this->success('Project fetched successfully.', [
            'project' => $project->load(['template.occasion'])->toApiArray(),
        ]);
    }

    public function update(UpdateProjectRequest $request, int $id): JsonResponse
    {
        $project = $request->project();

        if ($project === null) {
            return $this->error('Project not found.', 404);
        }

        $data = $request->safe()->only([
            'title',
            'recipient_name',
            'from_name',
            'content',
        ]);

        $project->fill($data);
        $project->status = ProjectStatus::Draft;
        $project->save();

        return $this->success('Project updated successfully.', [
            'project' => $project->fresh()?->load(['template.occasion'])->toApiArray(),
        ]);
    }

    public function uploads(Request $request, int $id): JsonResponse
    {
        $project = $this->ownedProject($request, $id);

        if ($project === null) {
            return $this->error('Project not found.', 404);
        }

        $uploads = $project->uploads()
            ->orderByDesc('id')
            ->get()
            ->map(fn (UploadsModel $upload) => $upload->toApiArray())
            ->values();

        return $this->success('Uploads fetched successfully.', [
            'uploads' => $uploads,
        ]);
    }

    public function uploadMedia(StoreUploadRequest $request, int $id, StoreUpload $store): JsonResponse
    {
        $project = $this->ownedProject($request, $id);

        if ($project === null) {
            return $this->error('Project not found.', 404);
        }

        $file = $request->file('file');
        $user = $request->user();

        if ($file === null || $user === null) {
            return $this->error('Please choose an image, video, or audio file.', 422);
        }

        $upload = $store->forProject($file, (int) $user->id, $project);

        return $this->success('File uploaded.', [
            'upload' => $upload->toApiArray(),
            'url' => $upload->url,
            'path' => $upload->path,
        ]);
    }

    private function ownedProject(Request $request, int $id): ?ProjectsModel
    {
        return ProjectsModel::query()
            ->where('id', $id)
            ->where('user_id', $request->user()?->id)
            ->first();
    }
}
