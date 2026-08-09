<?php

namespace App\Http\Controllers\View;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Support\SyncsRolePermissions;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PermissionController
{
    public function index(Request $request): View
    {
        $roles = Role::query()
            ->withCount(['permissions', 'users'])
            ->orderBy('role')
            ->get();

        $permissionQuery = Permission::query()->with('roles')->orderBy('display_name');

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $like = "%{$search}%";
            $permissionQuery->where(function ($q) use ($like) {
                $q->where('display_name', 'like', $like)
                    ->orWhere('name', 'like', $like)
                    ->orWhere('description', 'like', $like);
            });
        }

        $permissions = $permissionQuery->paginate(10)->withQueryString();

        $userQuery = User::with(['role.permissions', 'profileImage']);
        if ($request->filled('user_search')) {
            $userSearch = $request->string('user_search')->toString();
            $like = "%{$userSearch}%";
            $userQuery->where(function ($q) use ($like) {
                $q->where('name', 'like', $like)
                    ->orWhere('email', 'like', $like);
            });
        }

        $users = $userQuery->latest()->paginate(10, ['*'], 'users_page')->withQueryString();

        return view('dashboard.permissions.index', compact('roles', 'permissions', 'users'));
    }

    public function editRole(Role $role): View
    {
        $role->load(['permissions' => fn ($q) => $q->orderBy('display_name')])
            ->loadCount(['permissions', 'users']);

        $allPermissions = Permission::query()->orderBy('display_name')->get();
        $assignedPermissionIds = $role->permissions->pluck('id')->all();

        $roleUsers = User::query()
            ->with('profileImage')
            ->where('role_id', $role->id)
            ->orderBy('name')
            ->paginate(12);

        return view('dashboard.permissions.roles.edit', compact(
            'role',
            'allPermissions',
            'assignedPermissionIds',
            'roleUsers'
        ));
    }

    public function syncRolePermissions(Request $request, Role $role): \Illuminate\Http\RedirectResponse
    {
        $data = $request->validate([
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['uuid', 'exists:permissions,id'],
        ]);

        SyncsRolePermissions::syncPermissionsForRole($role, $data['permissions'] ?? []);

        return redirect()
            ->route('dashboard.permissions.roles.edit', $role)
            ->with('success', "Permissions updated for {$role->display_name}");
    }

    public function create(): View
    {
        $roles = Role::with('permissions')->orderBy('role')->get();
        $allPermissions = Permission::orderBy('display_name')->get();

        return view('dashboard.permissions.create', compact('roles', 'allPermissions'));
    }

    public function store(Request $request): \Illuminate\Http\RedirectResponse
    {
        $data = $request->validate([
            'display_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'roles' => 'array',
        ]);

        $permission = Permission::create([
            'name' => strtolower(str_replace(' ', '_', $data['display_name'])),
            'display_name' => $data['display_name'],
            'description' => $data['description'] ?? null,
        ]);

        SyncsRolePermissions::syncRolesForPermission($permission, $data['roles'] ?? []);

        return redirect()->route('dashboard.permissions.index')->with('success', 'Permission created successfully');
    }

    public function show(Permission $permission): View
    {
        $permission->load('roles');

        return view('dashboard.permissions.show', compact('permission'));
    }

    public function edit(Permission $permission): View
    {
        $permission->load('roles');

        // Eager-load relationship counts shown in the view
        $roles = Role::with('permissions')->orderBy('role')->get();
        $assignedRoles = $permission->roles->pluck('id')->toArray();

        $allPermissions = Permission::orderBy('display_name')->get();

        return view('dashboard.permissions.edit', compact('permission', 'roles', 'assignedRoles', 'allPermissions'));
    }

    public function update(Request $request, Permission $permission): \Illuminate\Http\RedirectResponse
    {
        $data = $request->validate([
            'display_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'roles' => 'array',
            'roles.*' => 'uuid',
        ]);


        $permission->update([
            'display_name' => $data['display_name'],
            'description' => $data['description'] ?? null,
        ]);

        SyncsRolePermissions::syncRolesForPermission($permission, $data['roles'] ?? []);

        return redirect()->route('dashboard.permissions.index')->with('success', 'Permission updated successfully');
    }

    public function destroy(Permission $permission): \Illuminate\Http\RedirectResponse
    {
        $permission->roles()->detach();
        $permission->delete();

        return redirect()->route('dashboard.permissions.index')->with('success', 'Permission deleted successfully');
    }
}
