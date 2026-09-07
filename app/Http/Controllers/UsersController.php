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
            'search' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', 'in:active,inactive'],
            'role' => ['nullable', 'in:customer,admin'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
            'offset' => ['nullable', 'integer', 'min:0'],
        ]);

        $search = trim($request->string('search')->toString());
        $limit = $request->integer('limit', 10);
        $offset = $request->integer('offset', 0);

        $query = UsersModel::query()
            ->where('is_deleted', false)
            ->when(
                $search !== '',
                function ($query) use ($search) {
                    $term = '%'.addcslashes($search, '%_\\').'%';

                    $query->where(function ($query) use ($term) {
                        $query
                            ->where('name', 'like', $term)
                            ->orWhere('email', 'like', $term)
                            ->orWhere('mobile_no', 'like', $term);
                    });
                },
            )
            ->when(
                $request->input('status') === 'active',
                fn ($query) => $query->where('is_active', true),
            )
            ->when(
                $request->input('status') === 'inactive',
                fn ($query) => $query->where('is_active', false),
            )
            ->when(
                $request->filled('role'),
                fn ($query) => $query->where('role', $request->string('role')->toString()),
            );

        $total = (clone $query)->count();

        $users = $query
            ->orderByDesc('id')
            ->offset($offset)
            ->limit($limit)
            ->get()
            ->map(fn (UsersModel $user) => $user->toApiArray())
            ->values();

        return $this->success('Users fetched successfully.', [
            'users' => $users,
            'total' => $total,
            'limit' => $limit,
            'offset' => $offset,
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
