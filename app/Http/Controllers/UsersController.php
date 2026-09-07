<?php

namespace App\Http\Controllers;

use App\Models\UsersModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UsersController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'role' => ['nullable', 'in:customer,admin'],
        ]);

        $users = UsersModel::query()
            ->where('is_deleted', false)
            ->when(
                $request->filled('role'),
                fn ($query) => $query->where('role', $request->string('role')->toString()),
            )
            ->orderByDesc('id')
            ->get()
            ->map(fn (UsersModel $user) => $user->toApiArray())
            ->values();

        return $this->success('Users fetched successfully.', [
            'users' => $users,
        ]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $user = $this->findLiveUser($id);

        if ($user === null) {
            return $this->error('User not found.', 404);
        }

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:120'],
            'email' => ['sometimes', 'email', 'max:150', Rule::unique('users', 'email')->ignore($user->id)],
            'mobile_no' => ['sometimes', 'nullable', 'string', 'max:20', Rule::unique('users', 'mobile_no')->ignore($user->id)],
            'dob' => ['sometimes', 'nullable', 'date', 'before:today'],
            'role' => ['sometimes', 'in:customer,admin'],
            'is_active' => ['sometimes', 'boolean'],
            'password' => ['sometimes', 'string', 'min:8'],
        ]);

        if (
            isset($data['role'])
            && $user->isAdmin()
            && $data['role'] !== UsersModel::ROLE_ADMIN
            && $this->isLastAdmin($user)
        ) {
            return $this->error('You cannot change the role of the last admin.', 422);
        }

        $user->fill(collect($data)->except('password')->all());

        if ($request->filled('password')) {
            $user->password = $request->string('password')->toString();
        }

        $user->save();

        if ($request->exists('is_active') && ! $user->is_active) {
            $user->tokens()->delete();
            $user->forceFill(['is_loggedin' => false])->save();
        }

        return $this->success('User updated successfully.', [
            'user' => $user->fresh()?->toApiArray(),
        ]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $user = $this->findLiveUser($id);

        if ($user === null) {
            return $this->error('User not found.', 404);
        }

        $actor = $request->user();

        if ($actor instanceof UsersModel && $actor->id === $user->id) {
            return $this->error('You cannot delete your own account.', 422);
        }

        if ($user->isAdmin() && $this->isLastAdmin($user)) {
            return $this->error('You cannot delete the last admin.', 422);
        }

        $user->tokens()->delete();
        $user->forceFill([
            'is_deleted' => true,
            'is_active' => false,
            'is_loggedin' => false,
        ])->save();

        return $this->success('User deleted successfully.');
    }

    private function findLiveUser(int $id): ?UsersModel
    {
        return UsersModel::query()
            ->where('id', $id)
            ->where('is_deleted', false)
            ->first();
    }

    private function isLastAdmin(UsersModel $user): bool
    {
        return UsersModel::query()
            ->where('role', UsersModel::ROLE_ADMIN)
            ->where('is_deleted', false)
            ->where('id', '!=', $user->id)
            ->doesntExist();
    }
}
