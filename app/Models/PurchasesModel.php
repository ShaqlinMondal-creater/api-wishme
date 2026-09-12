<?php

namespace App\Models;

use App\Enums\PurchaseStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'user_id',
    'template_id',
    'price',
    'status',
])]
class PurchasesModel extends Model
{
    protected $table = 't_purchases';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'status' => PurchaseStatus::class,
        ];
    }

    public function isPaid(): bool
    {
        return $this->status === PurchaseStatus::Paid;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(UsersModel::class, 'user_id');
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(TemplatesModel::class, 'template_id');
    }

    public function project(): HasOne
    {
        return $this->hasOne(ProjectsModel::class, 'purchase_id');
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
            'price' => $this->price,
            'status' => $this->status instanceof PurchaseStatus ? $this->status->value : $this->status,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
