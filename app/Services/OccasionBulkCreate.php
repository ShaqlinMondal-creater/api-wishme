<?php

namespace App\Services;

use App\Enums\OccasionType;
use App\Models\OccasionsModel;
use App\Models\TemplatesModel;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class OccasionBulkCreate
{
    public function __construct(private StoreUpload $store)
    {
    }

    /**
     * @return array{created: list<array<string, mixed>>, skipped: list<array<string, mixed>>, templates_linked: int}
     */
    public function run(int $userId): array
    {
        $path = database_path('data/occasions.json');

        if (! is_file($path)) {
            throw new RuntimeException('occasions.json was not found.');
        }

        $decoded = json_decode((string) file_get_contents($path), true);

        if (! is_array($decoded) || ! isset($decoded['occasions']) || ! is_array($decoded['occasions'])) {
            throw new RuntimeException('occasions.json must contain an occasions array.');
        }

        $created = [];
        $skipped = [];

        foreach ($decoded['occasions'] as $row) {
            if (! is_array($row)) {
                continue;
            }

            $title = trim((string) ($row['title'] ?? ''));
            $description = trim((string) ($row['description'] ?? ''));
            $image = trim((string) ($row['image'] ?? ''));
            $type = OccasionType::tryFrom((string) ($row['type'] ?? ''));

            if ($title === '' || $description === '' || $type === null) {
                continue;
            }

            $existing = OccasionsModel::query()->where('type', $type->value)->first();

            if ($existing !== null) {
                if ($existing->thumbnail_id === null) {
                    $this->attachDefaultImage($existing, $userId, $image !== '' ? $image : $type->value.'.png');
                }

                $skipped[] = $existing->fresh()?->load('thumbnail')->toApiArray() ?? $existing->toApiArray();
                continue;
            }

            $occasion = OccasionsModel::query()->create([
                'title' => $title,
                'description' => $description,
                'type' => $type->value,
                'thumbnail_id' => null,
            ]);

            $this->attachDefaultImage($occasion, $userId, $image !== '' ? $image : $type->value.'.png');
            $created[] = $occasion->fresh()?->load('thumbnail')->toApiArray() ?? $occasion->toApiArray();
        }

        $templatesLinked = $this->linkExistingTemplates();

        return [
            'created' => $created,
            'skipped' => $skipped,
            'templates_linked' => $templatesLinked,
        ];
    }

    private function attachDefaultImage(OccasionsModel $occasion, int $userId, string $filename): void
    {
        $source = database_path('data/occasions/'.basename($filename));

        if (! is_file($source)) {
            return;
        }

        $this->store->forOccasionFromPath($source, $userId, $occasion);
    }

    private function linkExistingTemplates(): int
    {
        if (! Schema::hasColumn('t_templates', 'occasion_slug')) {
            return 0;
        }

        $linked = 0;
        $occasions = OccasionsModel::query()->get();

        foreach ($occasions as $occasion) {
            $type = $occasion->type instanceof OccasionType ? $occasion->type->value : (string) $occasion->type;
            $linked += TemplatesModel::query()
                ->whereNull('occasion_id')
                ->where('occasion_slug', $type)
                ->update(['occasion_id' => $occasion->id]);
        }

        return $linked;
    }
}
