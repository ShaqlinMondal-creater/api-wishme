<?php

namespace App\Http\Middleware;

use App\Models\UsersModel;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();
        $role = $user instanceof UsersModel ? $user->roleValue() : null;

        if ($user === null || $role === null || ! in_array($role, $roles, true)) {
            return response()->json([
                'success' => false,
                'message' => 'You are not allowed to access this resource.',
            ], 403);
        }

        return $next($request);
    }
}
