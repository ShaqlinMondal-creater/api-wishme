<?php

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable([
    'name',
    'email',
    'password',
    'mobile_no',
    'role',
    'otp',
    'otp_expire',
    'mobile_verify_at',
    'google_id',
    'auth_provider',
    'is_active',
    'is_loggedin',
    'is_deleted',
    'dob',
])]
#[Hidden(['password', 'remember_token', 'otp'])]
class UsersModel extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    public const ROLE_CUSTOMER = 'customer';

    public const ROLE_ADMIN = 'admin';

    protected $table = 'users';

    protected static function newFactory(): UserFactory
    {
        return UserFactory::new();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'otp_expire' => 'datetime',
            'mobile_verify_at' => 'datetime',
            'dob' => 'date',
            'password' => 'hashed',
            'role' => UserRole::class,
            'is_active' => 'boolean',
            'is_loggedin' => 'boolean',
            'is_deleted' => 'boolean',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function roleValue(): string
    {
        return $this->role instanceof UserRole ? $this->role->value : (string) $this->role;
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(PurchasesModel::class, 'user_id');
    }

    public function projects(): HasMany
    {
        return $this->hasMany(ProjectsModel::class, 'user_id');
    }

    /**
     * @return array<string, mixed>
     */
    public function toApiArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'mobile_no' => $this->mobile_no,
            'role' => $this->roleValue(),
            'dob' => $this->dob?->toDateString(),
            'auth_provider' => $this->auth_provider,
            'mobile_verify_at' => $this->mobile_verify_at?->toIso8601String(),
            'is_active' => $this->is_active,
            'is_loggedin' => $this->is_loggedin,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
