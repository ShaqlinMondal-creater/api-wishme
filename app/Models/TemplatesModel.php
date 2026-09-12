<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'slug',
    'name',
    'description',
    'cover',
    'occasion_id',
    'price',
    'has_letter',
    'has_stories',
    'has_moments',
    'has_privacy',
    'has_surprise_gift',
    'is_active',
    'content',
])]
class TemplatesModel extends Model
{
    public const SLUG_MIDNIGHT_TOAST = 'midnight-toast';

    /**
     * @var list<int>
     */
    public const PRICES = [149, 249, 499];

    /**
     * @var array<string, string>
     */
    public const CONTENT_ROOMS = [
        'letter' => 'has_letter',
        'stories' => 'has_stories',
        'moments' => 'has_moments',
        'privacy' => 'has_privacy',
        'gifts' => 'has_surprise_gift',
    ];

    protected $table = 't_templates';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'has_letter' => 'boolean',
            'has_stories' => 'boolean',
            'has_moments' => 'boolean',
            'has_privacy' => 'boolean',
            'has_surprise_gift' => 'boolean',
            'is_active' => 'boolean',
            'content' => 'array',
        ];
    }

    public function allowsContentRoom(string $room): bool
    {
        $column = self::CONTENT_ROOMS[$room] ?? null;

        return $column !== null && (bool) $this->{$column};
    }

    /**
     * @return array<string, string>
     */
    public function contentRoomLabels(): array
    {
        return [
            'letter' => 'Letter',
            'stories' => 'Stories',
            'moments' => 'Moments',
            'privacy' => 'Privacy',
            'gifts' => 'Surprise gift',
        ];
    }

    public function occasion(): BelongsTo
    {
        return $this->belongsTo(OccasionsModel::class, 'occasion_id');
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(PurchasesModel::class, 'template_id');
    }

    public function projects(): HasMany
    {
        return $this->hasMany(ProjectsModel::class, 'template_id');
    }

    public function uploads(): HasMany
    {
        return $this->hasMany(UploadsModel::class, 'template_id');
    }

    /**
     * @return array<string, mixed>
     */
    public function toApiArray(): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'description' => $this->description,
            'cover' => $this->cover,
            'occasion_id' => $this->occasion_id,
            'occasion' => $this->occasion?->toApiArray(),
            'price' => $this->price,
            'has_letter' => $this->has_letter,
            'has_stories' => $this->has_stories,
            'has_moments' => $this->has_moments,
            'has_privacy' => $this->has_privacy,
            'has_surprise_gift' => $this->has_surprise_gift,
            'is_active' => $this->is_active,
            'content' => $this->content ?? [],
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
