<?php

namespace App\Models;

use App\Enums\ProjectStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'user_id',
    'template_id',
    'purchase_id',
    'title',
    'recipient_name',
    'from_name',
    'content',
    'status',
])]
class ProjectsModel extends Model
{
    protected $table = 'projects';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'content' => 'array',
            'status' => ProjectStatus::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(UsersModel::class, 'user_id');
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(TemplatesModel::class, 'template_id');
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(PurchasesModel::class, 'purchase_id');
    }

    public function uploads(): HasMany
    {
        return $this->hasMany(UploadsModel::class, 'project_id');
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
            'purchase_id' => $this->purchase_id,
            'title' => $this->title,
            'recipient_name' => $this->recipient_name,
            'from_name' => $this->from_name,
            'content' => $this->content ?? [],
            'status' => $this->status instanceof ProjectStatus ? $this->status->value : $this->status,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
