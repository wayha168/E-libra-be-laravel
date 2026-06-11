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

        if (!method_exists($user, 'hasRole') || !$user->hasRole($role)) {
            return $this->reject($request, 403, 'Forbidden');
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
