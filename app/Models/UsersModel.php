<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
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
            'is_active' => 'boolean',
            'is_loggedin' => 'boolean',
            'is_deleted' => 'boolean',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }
}
