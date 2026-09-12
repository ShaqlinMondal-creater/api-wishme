<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOccasionRequest;
use App\Http\Requests\StoreOccasionUploadRequest;
use App\Models\OccasionsModel;
use App\Services\OccasionBulkCreate;
use App\Services\StoreUpload;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class OccasionsController extends Controller
{
    public function occasionView(): JsonResponse
    {
        $occasions = OccasionsModel::query()
            ->with('thumbnail')
            ->orderBy('title')
            ->get()
            ->map(fn (OccasionsModel $occasion) => $occasion->toApiArray())
            ->values();

        return $this->success('Occasions fetched successfully.', [
            'occasions' => $occasions,
        ]);
    }

    public function occasionViewDetail(int $id): JsonResponse
    {
        $occasion = OccasionsModel::query()->with('thumbnail')->find($id);

        if ($occasion === null) {
            return $this->error('Occasion not found.', 404);
        }

        return $this->success('Occasion fetched successfully.', [
            'occasion' => $occasion->toApiArray(),
        ]);
    }

    public function occasionCreate(StoreOccasionRequest $request, StoreUpload $store): JsonResponse
    {
        $occasion = OccasionsModel::query()->create($request->safe()->only([
            'title',
            'description',
            'type',
            'thumbnail_id',
        ]));

        if ($occasion->thumbnail_id === null) {
            $this->attachTypeDefault($store, $occasion, (int) $request->user()?->id);
        }

        return $this->success('Occasion created successfully.', [
            'occasion' => $occasion->fresh()?->load('thumbnail')->toApiArray(),
        ], 201);
    }

    public function occasionBulkCreate(Request $request, OccasionBulkCreate $bulkCreate): JsonResponse
    {
        $user = $request->user();

        if ($user === null) {
            return $this->error('Please sign in.', 401);
        }

        try {
            $result = $bulkCreate->run((int) $user->id);
        } catch (RuntimeException $error) {
            return $this->error($error->getMessage(), 422);
        }

        return $this->success('Occasions created from JSON.', [
            'created_count' => count($result['created']),
            'skipped_count' => count($result['skipped']),
            'templates_linked' => $result['templates_linked'],
            'created' => $result['created'],
            'skipped' => $result['skipped'],
        ], count($result['created']) > 0 ? 201 : 200);
    }

    public function occasionUpdate(StoreOccasionRequest $request, int $id): JsonResponse
    {
        $occasion = OccasionsModel::query()->find($id);

        if ($occasion === null) {
            return $this->error('Occasion not found.', 404);
        }

        $occasion->fill($request->safe()->only([
            'title',
            'description',
            'type',
            'thumbnail_id',
        ]));
        $occasion->save();

        return $this->success('Occasion updated successfully.', [
            'occasion' => $occasion->fresh()?->load('thumbnail')->toApiArray(),
        ]);
    }

    public function occasionDelete(int $id): JsonResponse
    {
        $occasion = OccasionsModel::query()->find($id);

        if ($occasion === null) {
            return $this->error('Occasion not found.', 404);
        }

        if ($occasion->templates()->exists()) {
            return $this->error('This occasion cannot be deleted because templates use it.', 422);
        }

        $occasion->delete();

        return $this->success('Occasion deleted successfully.');
    }

    public function occasionUpload(StoreOccasionUploadRequest $request, int $id, StoreUpload $store): JsonResponse
    {
        $occasion = OccasionsModel::query()->find($id);

        if ($occasion === null) {
            return $this->error('Occasion not found.', 404);
        }

        $file = $request->file('file');
        $user = $request->user();

        if ($file === null || $user === null) {
            return $this->error('Please choose an image.', 422);
        }

        $upload = $store->forOccasion($file, (int) $user->id, $occasion);

        return $this->success('File uploaded.', [
            'occasion' => $occasion->fresh()?->load('thumbnail')->toApiArray(),
            'upload' => $upload->toApiArray(),
            'url' => $upload->url,
        ]);
    }

    private function attachTypeDefault(StoreUpload $store, OccasionsModel $occasion, int $userId): void
    {
        if ($userId < 1) {
            return;
        }

        $type = $occasion->type instanceof \App\Enums\OccasionType
            ? $occasion->type->value
            : (string) $occasion->type;
        $source = database_path('data/occasions/'.$type.'.png');

        if (! is_file($source)) {
            return;
        }

        $store->forOccasionFromPath($source, $userId, $occasion);
    }
}
