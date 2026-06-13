<?php

namespace App\Http\Controllers\View;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PermissionController
{
    public function index(Request $request): View
    {
        $query = Permission::query()->with('roles');

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where('display_name', 'like', "%{$search}%");
        }

        $permissions = $query->latest()->paginate(10);
        $roles = Role::orderBy('role')->get();
        $users = User::with(['role.permissions', 'profileImage'])->latest()->paginate(10, ['*'], 'users_page');

        return view('dashboard.permissions.index', compact('permissions', 'roles', 'users'));
    }

    public function create(): View
    {
        $roles = Role::orderBy('role')->get();
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

        if (!empty($data['roles'])) {
            $permission->roles()->sync($data['roles']);
        }

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

        $permission->roles()->sync($data['roles'] ?? []);

        return redirect()->route('dashboard.permissions.index')->with('success', 'Permission updated successfully');
    }

    public function destroy(Permission $permission): \Illuminate\Http\RedirectResponse
    {
        $permission->roles()->detach();
        $permission->delete();

        return redirect()->route('dashboard.permissions.index')->with('success', 'Permission deleted successfully');
    }
}
