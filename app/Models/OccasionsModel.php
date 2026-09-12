<?php

namespace App\Models;

use App\Enums\OccasionType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'title',
    'description',
    'type',
    'thumbnail_id',
])]
class OccasionsModel extends Model
{
    protected $table = 't_occasion';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => OccasionType::class,
            'thumbnail_id' => 'integer',
        ];
    }

    public function thumbnail(): BelongsTo
    {
        return $this->belongsTo(UploadsModel::class, 'thumbnail_id');
    }

    public function templates(): HasMany
    {
        return $this->hasMany(TemplatesModel::class, 'occasion_id');
    }

    public function uploads(): HasMany
    {
        return $this->hasMany(UploadsModel::class, 'occasion_id');
    }

    /**
     * @return array<string, mixed>
     */
    public function toApiArray(): array
    {
        $type = $this->type instanceof OccasionType ? $this->type : OccasionType::tryFrom((string) $this->type);

        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'type' => $type?->value ?? $this->type,
            'thumbnail_id' => $this->thumbnail_id,
            'thumbnail_url' => $this->thumbnail?->url,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
