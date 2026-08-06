<?php

namespace App\Http\Controllers\Api;

use App\Models\Permission;
use App\Support\SyncsRolePermissions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PermissionController extends Controller
{
    /**
     * List permissions.
     * - admin / super_admin: all permissions with assigned roles
     * - others: permissions for the authenticated user's role
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $perPage = min((int) $request->integer('per_page', 50), 100);

        if ($user->isSuperAdmin() || $user->isAdmin()) {
            $query = Permission::query()->with(['roles' => fn ($q) => $q->orderBy('role')]);

            if ($request->filled('search')) {
                $search = $request->string('search')->toString();
                $like = "%{$search}%";
                $query->where(function ($q) use ($like) {
                    $q->where('display_name', 'like', $like)
                        ->orWhere('name', 'like', $like)
                        ->orWhere('description', 'like', $like);
                });
            }

            $permissions = $query->orderBy('display_name')->paginate($perPage);

            $permissions->getCollection()->transform(fn (Permission $p) => $this->formatPermission($p));

            return response()->json([
                'message' => 'Permissions fetched successfully',
                'data' => $permissions,
            ]);
        }

        $roleId = $user->role_id ?? $user->role?->id;

        $query = Permission::query()
            ->when(
                $roleId,
                fn ($q) => $q->whereHas('roles', fn ($rq) => $rq->where('user_roles.id', $roleId)),
                fn ($q) => $q->whereRaw('1 = 0')
            );

        $permissions = $query->orderBy('display_name')->paginate($perPage);

        $permissions->getCollection()->transform(fn (Permission $p) => [
            'id' => $p->id,
            'name' => $p->name,
            'display_name' => $p->display_name,
            'description' => $p->description,
        ]);

        return response()->json([
            'message' => 'Permissions fetched successfully',
            'data' => $permissions,
        ]);
    }

    public function show(Permission $permission): JsonResponse
    {
        $permission->load(['roles' => fn ($q) => $q->orderBy('role')]);

        return response()->json([
            'message' => 'Permission fetched successfully',
            'data' => $this->formatPermission($permission),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'display_name' => ['required', 'string', 'max:255'],
            'name' => ['nullable', 'string', 'max:255', 'unique:permissions,name'],
            'description' => ['nullable', 'string'],
            'role_ids' => ['nullable', 'array'],
            'role_ids.*' => ['uuid', 'exists:user_roles,id'],
        ]);

        $name = $validated['name']
            ?? strtolower(str_replace(' ', '_', $validated['display_name']));

        $permission = Permission::create([
            'name' => $name,
            'display_name' => $validated['display_name'],
            'description' => $validated['description'] ?? null,
        ]);

        SyncsRolePermissions::syncRolesForPermission($permission, $validated['role_ids'] ?? []);

        $permission->load(['roles' => fn ($q) => $q->orderBy('role')]);

        return response()->json([
            'message' => 'Permission created successfully',
            'data' => $this->formatPermission($permission),
        ], 201);
    }

    public function update(Request $request, Permission $permission): JsonResponse
    {
        $validated = $request->validate([
            'display_name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'role_ids' => ['present', 'array'],
            'role_ids.*' => ['uuid', 'exists:user_roles,id'],
        ]);

        $permission->update([
            'display_name' => $validated['display_name'],
            'description' => $validated['description'] ?? null,
        ]);

        SyncsRolePermissions::syncRolesForPermission($permission, $validated['role_ids']);

        $permission->load(['roles' => fn ($q) => $q->orderBy('role')]);

        return response()->json([
            'message' => 'Permission updated successfully',
            'data' => $this->formatPermission($permission),
        ]);
    }

    public function destroy(Permission $permission): JsonResponse
    {
        $permission->roles()->detach();
        $permission->delete();

        return response()->json([
            'message' => 'Permission deleted successfully',
            'data' => null,
        ]);
    }

    private function formatPermission(Permission $permission): array
    {
        return [
            'id' => $permission->id,
            'name' => $permission->name,
            'display_name' => $permission->display_name,
            'description' => $permission->description,
            'roles' => $permission->relationLoaded('roles')
                ? $permission->roles->map(fn ($r) => [
                    'id' => $r->id,
                    'role' => $r->role,
                    'display_name' => $r->display_name,
                ])->values()
                : [],
        ];
    }
}
