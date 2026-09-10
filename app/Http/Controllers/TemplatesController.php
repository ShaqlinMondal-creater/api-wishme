<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTemplateRequest;
use App\Http\Requests\StoreUploadRequest;
use App\Http\Requests\UpdateTemplateContentRequest;
use App\Http\Requests\UpdateTemplateRequest;
use App\Models\TemplatesModel;
use App\Models\UploadsModel;
use App\Services\StoreUpload;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TemplatesController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'occasion' => ['nullable', 'in:'.implode(',', TemplatesModel::OCCASIONS)],
        ]);

        $templates = TemplatesModel::query()
            ->where('is_active', true)
            ->when(
                $request->filled('occasion'),
                fn ($query) => $query->where('occasion', $request->string('occasion')->toString()),
            )
            ->orderBy('name')
            ->get()
            ->map(fn (TemplatesModel $template) => $template->toApiArray())
            ->values();

        return $this->success('Templates fetched successfully.', [
            'templates' => $templates,
        ]);
    }

    public function show(string $id): JsonResponse
    {
        $template = $this->findPublicTemplate($id);

        if ($template === null) {
            return $this->error('Template not found.', 404);
        }

        return $this->success('Template fetched successfully.', [
            'template' => $template->toApiArray(),
        ]);
    }

    public function adminIndex(Request $request): JsonResponse
    {
        $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'occasion' => ['nullable', 'in:'.implode(',', TemplatesModel::OCCASIONS)],
            'status' => ['nullable', 'in:active,inactive'],
        ]);

        $search = trim($request->string('search')->toString());

        $templates = TemplatesModel::query()
            ->when(
                $search !== '',
                function ($query) use ($search) {
                    $term = '%'.addcslashes($search, '%_\\').'%';

                    $query->where(function ($query) use ($term) {
                        $query
                            ->where('name', 'like', $term)
                            ->orWhere('slug', 'like', $term);
                    });
                },
            )
            ->when(
                $request->filled('occasion'),
                fn ($query) => $query->where('occasion', $request->string('occasion')->toString()),
            )
            ->when(
                $request->input('status') === 'active',
                fn ($query) => $query->where('is_active', true),
            )
            ->when(
                $request->input('status') === 'inactive',
                fn ($query) => $query->where('is_active', false),
            )
            ->orderByDesc('id')
            ->get()
            ->map(fn (TemplatesModel $template) => $template->toApiArray())
            ->values();

        return $this->success('Templates fetched successfully.', [
            'templates' => $templates,
        ]);
    }

    public function store(StoreTemplateRequest $request): JsonResponse
    {
        $template = TemplatesModel::query()->create([
            ...$request->safe()->all(),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return $this->success('Template created successfully.', [
            'template' => $template->toApiArray(),
        ], 201);
    }

    public function update(UpdateTemplateRequest $request, int $id): JsonResponse
    {
        $template = TemplatesModel::query()->find($id);

        if ($template === null) {
            return $this->error('Template not found.', 404);
        }

        $template->fill($request->safe()->all());
        $template->save();

        return $this->success('Template updated successfully.', [
            'template' => $template->fresh()?->toApiArray(),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $template = TemplatesModel::query()->find($id);

        if ($template === null) {
            return $this->error('Template not found.', 404);
        }

        if ($template->purchases()->exists() || $template->projects()->exists()) {
            return $this->error('This template cannot be deleted because it has purchases or projects.', 422);
        }

        $template->delete();

        return $this->success('Template deleted successfully.');
    }

    public function adminShow(int $id): JsonResponse
    {
        $template = TemplatesModel::query()->find($id);

        if ($template === null) {
            return $this->error('Template not found.', 404);
        }

        return $this->success('Template fetched successfully.', [
            'template' => $template->toApiArray(),
        ]);
    }

    public function updateContent(UpdateTemplateContentRequest $request, int $id): JsonResponse
    {
        $template = TemplatesModel::query()->find($id);

        if ($template === null) {
            return $this->error('Template not found.', 404);
        }

        $template->content = $request->validated('content');
        $template->save();

        return $this->success('Template content saved.', [
            'template' => $template->fresh()?->toApiArray(),
        ]);
    }

    public function uploads(int $id): JsonResponse
    {
        $template = TemplatesModel::query()->find($id);

        if ($template === null) {
            return $this->error('Template not found.', 404);
        }

        $uploads = $template->uploads()
            ->whereNull('project_id')
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
        $template = TemplatesModel::query()->find($id);

        if ($template === null) {
            return $this->error('Template not found.', 404);
        }

        $file = $request->file('file');
        $user = $request->user();

        if ($file === null || $user === null) {
            return $this->error('Please choose an image, video, or audio file.', 422);
        }

        $upload = $store->forTemplate($file, (int) $user->id, $template);

        return $this->success('File uploaded.', [
            'upload' => $upload->toApiArray(),
            'url' => $upload->url,
            'path' => $upload->path,
        ]);
    }

    private function findPublicTemplate(string $id): ?TemplatesModel
    {
        $query = TemplatesModel::query()->where('is_active', true);

        if (ctype_digit($id)) {
            return $query->where('id', (int) $id)->first();
        }

        return $query->where('slug', $id)->first();
    }
}
