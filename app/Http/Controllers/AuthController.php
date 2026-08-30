<?php

namespace App\Http\Controllers;

use App\Models\UsersModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:150', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'mobile_no' => ['nullable', 'string', 'max:20', 'unique:users,mobile_no'],
            'dob' => ['nullable', 'date', 'before:today'],
        ]);

        $user = UsersModel::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'mobile_no' => $data['mobile_no'] ?? null,
            'dob' => $data['dob'] ?? null,
            'role' => UsersModel::ROLE_CUSTOMER,
            'auth_provider' => 'email',
            'is_active' => true,
            'is_loggedin' => true,
            'is_deleted' => false,
        ]);

        $token = $user->createToken('wishme-api', [$user->role])->plainTextToken;

        return $this->success('Registered successfully.', [
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => $this->userPayload($user),
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required_without:mobile_no', 'nullable', 'email'],
            'mobile_no' => ['required_without:email', 'nullable', 'string', 'max:20'],
            'password' => ['required', 'string'],
            'role' => ['nullable', 'in:customer,admin'],
        ]);

        $user = $this->findUser($request->input('email'), $request->input('mobile_no'));

        if (
            $user === null
            || $user->is_deleted
            || ! Hash::check($request->string('password')->toString(), $user->password)
        ) {
            return $this->error('Invalid login details.', 401);
        }

        if (! $user->is_active) {
            return $this->error('Your account is inactive.', 403);
        }

        if ($request->filled('role') && $user->role !== $request->string('role')->toString()) {
            return $this->error('You cannot login with this role.', 403);
        }

        $user->forceFill(['is_loggedin' => true])->save();
        $user->tokens()->delete();

        $token = $user->createToken('wishme-api', [$user->role])->plainTextToken;

        return $this->success('Logged in successfully.', [
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => $this->userPayload($user),
        ]);
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required_without:mobile_no', 'nullable', 'email'],
            'mobile_no' => ['required_without:email', 'nullable', 'string', 'max:20'],
        ]);

        $user = $this->findUser($request->input('email'), $request->input('mobile_no'));

        if ($user !== null && ! $user->is_deleted && $user->is_active) {
            $otp = (string) random_int(100000, 999999);

            $user->forceFill([
                'otp' => Hash::make($otp),
                'otp_expire' => now()->addMinutes(10),
            ])->save();

            Log::info('WISHME password OTP generated.', [
                'user_id' => $user->id,
                'otp' => $otp,
            ]);
        }

        return $this->success('If the account exists, an OTP has been sent.');
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required_without:mobile_no', 'nullable', 'email'],
            'mobile_no' => ['required_without:email', 'nullable', 'string', 'max:20'],
            'otp' => ['required', 'string', 'size:6'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = $this->findUser($request->input('email'), $request->input('mobile_no'));

        if (
            $user === null
            || $user->otp === null
            || $user->otp_expire === null
            || $user->otp_expire->isPast()
            || ! Hash::check($request->string('otp')->toString(), $user->otp)
        ) {
            return $this->error('Invalid or expired OTP.', 422);
        }

        $user->forceFill([
            'password' => $request->string('password')->toString(),
            'otp' => null,
            'otp_expire' => null,
            'is_loggedin' => false,
        ])->save();

        $user->tokens()->delete();

        return $this->success('Password reset successfully. Please login.');
    }

    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user instanceof UsersModel) {
            $token = $user->currentAccessToken();

            if ($token !== null && method_exists($token, 'delete')) {
                $token->delete();
            }

            $user->forceFill(['is_loggedin' => false])->save();
        }

        return $this->success('Logged out successfully.');
    }

    public function profile(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user instanceof UsersModel) {
            return $this->error('Unauthenticated.', 401);
        }

        return $this->success('Profile fetched successfully.', [
            'user' => $this->userPayload($user),
        ]);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user instanceof UsersModel) {
            return $this->error('Unauthenticated.', 401);
        }

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:120'],
            'email' => ['sometimes', 'email', 'max:150', Rule::unique('users', 'email')->ignore($user->id)],
            'mobile_no' => ['sometimes', 'nullable', 'string', 'max:20', Rule::unique('users', 'mobile_no')->ignore($user->id)],
            'dob' => ['sometimes', 'nullable', 'date', 'before:today'],
            'password' => ['sometimes', 'string', 'min:8', 'confirmed'],
        ]);

        $user->fill(collect($data)->except('password')->all());

        if ($request->filled('password')) {
            $user->password = $request->string('password')->toString();
        }

        $user->save();

        return $this->success('Profile updated successfully.', [
            'user' => $this->userPayload($user->fresh()),
        ]);
    }

    private function findUser(?string $email, ?string $mobileNo): ?UsersModel
    {
        return UsersModel::query()
            ->when(
                $email,
                fn ($query) => $query->where('email', $email),
                fn ($query) => $query->where('mobile_no', $mobileNo),
            )
            ->first();
    }

    /**
     * @return array<string, mixed>
     */
    private function userPayload(?UsersModel $user): array
    {
        if ($user === null) {
            return [];
        }

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'mobile_no' => $user->mobile_no,
            'role' => $user->role,
            'dob' => $user->dob?->toDateString(),
            'auth_provider' => $user->auth_provider,
            'mobile_verify_at' => $user->mobile_verify_at?->toIso8601String(),
            'is_active' => $user->is_active,
            'is_loggedin' => $user->is_loggedin,
        ];
    }
}
