<?php

namespace Database\Factories;

use App\Models\UsersModel;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<UsersModel>
 */
class UserFactory extends Factory
{
    protected $model = UsersModel::class;

    protected static ?string $password;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'mobile_no' => fake()->unique()->numerify('9#########'),
            'role' => UsersModel::ROLE_CUSTOMER,
            'auth_provider' => 'email',
            'is_active' => true,
            'is_loggedin' => false,
            'is_deleted' => false,
            'dob' => fake()->date(),
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
