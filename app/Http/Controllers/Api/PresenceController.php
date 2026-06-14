<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Support\UserPresence;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PresenceController extends Controller
{
    public function heartbeat(Request $request): JsonResponse
    {
        $user = UserPresence::touch($request->user());

        return response()->json([
            'message' => 'Presence updated',
            'data' => UserPresence::format($user),
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $viewer = $request->user();

        if (!$viewer->isSuperAdmin() && !$viewer->isAdmin()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $users = User::query()
            ->with('role:id,role,display_name')
            ->latest('last_seen_at')
            ->get(['id', 'name', 'email', 'role_id', 'last_seen_at']);

        return response()->json([
            'message' => 'User presence fetched successfully',
            'data' => UserPresence::snapshot($users),
        ]);
    }
}
