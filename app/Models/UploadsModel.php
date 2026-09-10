<?php

namespace App\Models;

use App\Enums\UploadKind;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'template_id',
    'project_id',
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

    protected $table = 'uploads';

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
