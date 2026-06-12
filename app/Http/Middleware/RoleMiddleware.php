<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $user = $request->user();

        if (!$user) {
            return $this->reject($request, 401, 'Unauthenticated');
        }

        if (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
            return $next($request);
        }

        if (!method_exists($user, 'hasRole')) {
            return $this->reject($request, 403, 'Insufficient access rights');
        }

        // Routes often pass multiple roles like: role:admin,author,user
        $roles = array_values(array_filter(array_map('trim', explode(',', $role))));

        $allowed = in_array(true, array_map(fn($r) => $user->hasRole($r), $roles), true);

        if (!$allowed) {
            return $this->reject($request, 403, 'Insufficient access rights');
        }

        return $next($request);
    }

    private function reject(Request $request, int $status, string $message): Response
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $message], $status);
        }

        if ($status === 401) {
            return redirect()->guest(route('login'));
        }

        abort($status, $message);
    }
}
