<?php

namespace App\Models;

use App\Enums\UploadKind;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'user_id',
    'template_id',
    'project_id',
    'occasion_id',
    'kind',
    'disk',
    'path',
    'url',
    'original_name',
    'mime',
    'size',
])]
class UploadsModel extends Model
{
    /**
     * @var list<string>
     */
    public const MIMES = [
        'image/jpeg',
        'image/png',
        'image/webp',
        'image/gif',
        'video/mp4',
        'video/webm',
        'audio/mpeg',
        'audio/mp3',
        'audio/wav',
        'audio/x-wav',
        'audio/ogg',
        'audio/mp4',
        'audio/aac',
        'audio/webm',
    ];

    /**
     * @var list<string>
     */
    public const IMAGE_EXTENSIONS = [
        'jpg',
        'jpeg',
        'png',
        'webp',
        'gif',
    ];

    /**
     * @var list<string>
     */
    public const EXTENSIONS = [
        'jpg',
        'jpeg',
        'png',
        'webp',
        'gif',
        'mp4',
        'webm',
        'mp3',
        'wav',
        'ogg',
        'm4a',
        'aac',
    ];

    /**
     * @var array<string, string>
     */
    public const MIME_BY_EXTENSION = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'webp' => 'image/webp',
        'gif' => 'image/gif',
        'mp4' => 'video/mp4',
        'webm' => 'video/webm',
        'mp3' => 'audio/mpeg',
        'wav' => 'audio/wav',
        'ogg' => 'audio/ogg',
        'm4a' => 'audio/mp4',
        'aac' => 'audio/aac',
    ];

    protected $table = 't_uploads';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'kind' => UploadKind::class,
            'size' => 'integer',
        ];
    }

    public static function kindFromExtension(string $extension): UploadKind
    {
        $mime = self::MIME_BY_EXTENSION[strtolower($extension)] ?? 'application/octet-stream';

        return self::kindFromMime($mime);
    }

    public static function kindFromMime(string $mime): UploadKind
    {
        if (str_starts_with($mime, 'video/')) {
            return UploadKind::Video;
        }

        if (str_starts_with($mime, 'audio/')) {
            return UploadKind::Audio;
        }

        return UploadKind::Image;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(UsersModel::class, 'user_id');
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(TemplatesModel::class, 'template_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(ProjectsModel::class, 'project_id');
    }

    public function occasion(): BelongsTo
    {
        return $this->belongsTo(OccasionsModel::class, 'occasion_id');
    }

    public function coveredTemplates(): HasMany
    {
        return $this->hasMany(TemplatesModel::class, 'cover_id');
    }

    public function thumbnailOccasions(): HasMany
    {
        return $this->hasMany(OccasionsModel::class, 'thumbnail_id');
    }

    /**
     * @return array<string, mixed>
     */
    public function toApiArray(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'template_id' => $this->template_id,
            'project_id' => $this->project_id,
            'occasion_id' => $this->occasion_id,
            'kind' => $this->kind instanceof UploadKind ? $this->kind->value : $this->kind,
            'disk' => $this->disk,
            'path' => $this->path,
            'url' => $this->url,
            'original_name' => $this->original_name,
            'mime' => $this->mime,
            'size' => $this->size,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
