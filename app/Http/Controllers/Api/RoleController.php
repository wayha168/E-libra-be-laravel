<?php

namespace App\Http\Controllers\Api;

use App\Models\Role;
use App\Support\SyncsRolePermissions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    public function index(): JsonResponse
    {
        $roles = Role::query()
            ->with(['permissions' => fn ($q) => $q->orderBy('display_name')])
            ->withCount('permissions')
            ->orderBy('role')
            ->get()
            ->map(fn (Role $role) => $this->formatRole($role));

        return response()->json([
            'message' => 'Roles fetched successfully',
            'data' => $roles,
        ]);
    }

    public function show(Role $role): JsonResponse
    {
        $role->load(['permissions' => fn ($q) => $q->orderBy('display_name')])
            ->loadCount('permissions');

        return response()->json([
            'message' => 'Role fetched successfully',
            'data' => $this->formatRole($role),
        ]);
    }

    public function syncPermissions(Request $request, Role $role): JsonResponse
    {
        $validated = $request->validate([
            'permission_ids' => ['present', 'array'],
            'permission_ids.*' => ['uuid', 'exists:permissions,id'],
        ]);

        SyncsRolePermissions::syncPermissionsForRole($role, $validated['permission_ids']);

        $role->load(['permissions' => fn ($q) => $q->orderBy('display_name')])
            ->loadCount('permissions');

        return response()->json([
            'message' => 'Role permissions updated successfully',
            'data' => $this->formatRole($role),
        ]);
    }

    private function formatRole(Role $role): array
    {
        return [
            'id' => $role->id,
            'role' => $role->role,
            'display_name' => $role->display_name,
            'permissions_count' => $role->permissions_count ?? $role->permissions->count(),
            'permissions' => $role->permissions->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'display_name' => $p->display_name,
                'description' => $p->description,
            ])->values(),
        ];
    }
}
