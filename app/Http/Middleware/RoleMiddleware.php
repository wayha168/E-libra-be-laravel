<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (!$user) {
            return $this->reject($request, 401, 'Unauthenticated');
        }

        if (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
            return $next($request);
        }

        if (!method_exists($user, 'hasRole')) {
            return $this->reject($request, 403, 'Credentials are invalid');
        }

        $roles = array_values(array_filter(array_map('trim', $roles)));

        if ($roles === []) {
            return $this->reject($request, 403, 'Credentials are invalid');
        }

        $allowed = in_array(true, array_map(fn ($role) => $user->hasRole($role), $roles), true);

        if (!$allowed) {
            return $this->reject($request, 403, 'Credentials are invalid');
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
