<?php

namespace App\Services;

use App\Enums\OccasionType;
use App\Models\OccasionsModel;
use App\Models\TemplatesModel;
use RuntimeException;

class TemplateBulkCreate
{
    public function __construct(private StoreUpload $store)
    {
    }

    /**
     * @return array{created: list<array<string, mixed>>, skipped: list<array<string, mixed>>, covers_attached: int, missing_occasions: list<string>}
     */
    public function run(int $userId): array
    {
        $path = database_path('data/templates.json');

        if (! is_file($path)) {
            throw new RuntimeException('templates.json was not found.');
        }

        $decoded = json_decode((string) file_get_contents($path), true);

        if (! is_array($decoded) || ! isset($decoded['templates']) || ! is_array($decoded['templates'])) {
            throw new RuntimeException('templates.json must contain a templates array.');
        }

        $occasions = OccasionsModel::query()->get()->keyBy(
            function (OccasionsModel $occasion) {
                $type = $occasion->type instanceof OccasionType ? $occasion->type->value : (string) $occasion->type;

                return $type;
            },
        );

        if ($occasions->isEmpty()) {
            throw new RuntimeException('Create occasions from JSON first.');
        }

        $created = [];
        $skipped = [];
        $missingOccasions = [];
        $coversAttached = 0;

        foreach ($decoded['templates'] as $row) {
            if (! is_array($row)) {
                continue;
            }

            $slug = strtolower(trim((string) ($row['slug'] ?? '')));
            $name = trim((string) ($row['name'] ?? ''));
            $description = trim((string) ($row['description'] ?? ''));
            $image = trim((string) ($row['image'] ?? ''));
            $type = OccasionType::tryFrom((string) ($row['occasion'] ?? $row['type'] ?? ''));
            $price = (int) ($row['price'] ?? 0);

            if (
                $slug === ''
                || $name === ''
                || $description === ''
                || $type === null
                || $price < 0
            ) {
                continue;
            }

            $occasion = $occasions->get($type->value);

            if ($occasion === null) {
                $missingOccasions[] = $type->value;
                continue;
            }

            $existing = TemplatesModel::query()->where('slug', $slug)->first();

            if ($existing !== null) {
                if ($existing->occasion_id === null) {
                    $existing->occasion_id = $occasion->id;
                    $existing->save();
                }

                if ($this->attachDefaultCover($existing, $userId, $image !== '' ? $image : $slug.'.png')) {
                    $coversAttached++;
                }

                $skipped[] = $existing->fresh()?->load(['occasion.thumbnail', 'coverUpload'])->toApiArray()
                    ?? $existing->toApiArray();
                continue;
            }

            $template = TemplatesModel::query()->create([
                'slug' => $slug,
                'name' => $name,
                'description' => $description,
                'occasion_id' => $occasion->id,
                'price' => $price,
                'has_letter' => (bool) ($row['has_letter'] ?? true),
                'has_stories' => (bool) ($row['has_stories'] ?? false),
                'has_moments' => (bool) ($row['has_moments'] ?? true),
                'has_privacy' => (bool) ($row['has_privacy'] ?? true),
                'has_surprise_gift' => (bool) ($row['has_surprise_gift'] ?? false),
                'is_active' => array_key_exists('is_active', $row) ? (bool) $row['is_active'] : true,
            ]);

            if ($this->attachDefaultCover($template, $userId, $image !== '' ? $image : $slug.'.png')) {
                $coversAttached++;
            }

            $created[] = $template->fresh()?->load(['occasion.thumbnail', 'coverUpload'])->toApiArray()
                ?? $template->toApiArray();
        }

        return [
            'created' => $created,
            'skipped' => $skipped,
            'covers_attached' => $coversAttached,
            'missing_occasions' => array_values(array_unique($missingOccasions)),
        ];
    }

    private function attachDefaultCover(TemplatesModel $template, int $userId, string $filename): bool
    {
        if ($userId < 1 || $template->cover_id) {
            return false;
        }

        $source = database_path('data/templates/'.basename($filename));

        if (! is_file($source)) {
            return false;
        }

        $this->store->forTemplateFromPath($source, $userId, $template);

        return true;
    }
}
