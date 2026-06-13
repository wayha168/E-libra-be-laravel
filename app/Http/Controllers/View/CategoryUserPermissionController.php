<?php

namespace App\Http\Controllers\View;

use App\Http\Requests\StoreCategoryUserPermissionsRequest;
use App\Models\Category;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\UserCategoryPermission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CategoryUserPermissionController
{
    public function edit(Category $category): View
    {
        // Only admin dashboard is routed, but still keep policy checks.
        // CategoryPolicy currently checks role permissions for create/update/view.
        // We'll authorize update to allow editing permissions.
        app('Illuminate\\Contracts\\Auth\\Access\\Gate')->authorize('update', $category);

        // Users selectable
        $users = User::query()->orderBy('name')->get();

        // We need which category-related permissions exist.
        // Convention: permissions.name uses snake_case like view_categories/create_categories etc.
        $permissions = Permission::query()
            ->whereIn('name', [
                'view_categories',
                'create_categories',
                'edit_categories',
                'delete_categories',
            ])
            ->orderBy('display_name')
            ->get();

        $selected = UserCategoryPermission::query()
            ->where('category_id', $category->id)
            ->get()
            ->groupBy('user_id');

        $selectedPermissionIdsByUser = [];
        foreach ($selected as $userId => $rows) {
            $selectedPermissionIdsByUser[(string) $userId] = $rows->pluck('permission_id')->all();
        }

        return view('dashboard.categories.permissions.edit', [
            'category' => $category,
            'users' => $users,
            'permissions' => $permissions,
            'selectedPermissionIdsByUser' => $selectedPermissionIdsByUser,
        ]);
    }

    public function update(StoreCategoryUserPermissionsRequest $request, Category $category): RedirectResponse
    {
        app('Illuminate\\Contracts\\Auth\\Access\\Gate')->authorize('update', $category);

        $payload = $request->validated();

        $permissionIds = collect($payload['permissions'] ?? [])
            ->filter()
            ->values();

        $userIds = collect($payload['users'] ?? [])
            ->filter()
            ->values();

        // Remove existing assignments for this category for selected users only
        // (to keep it simple and avoid wiping other users).
        UserCategoryPermission::query()
            ->where('category_id', $category->id)
            ->whereIn('user_id', $userIds)
            ->delete();

        // Insert new assignments
        $rows = [];
        foreach ($userIds as $userId) {
            foreach ($permissionIds as $permissionId) {
                $rows[] = [
                    'id' => (string) \Illuminate\Support\Str::uuid(),
                    'user_id' => $userId,
                    'category_id' => $category->id,
                    'permission_id' => $permissionId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        if (!empty($rows)) {
            UserCategoryPermission::insert($rows);
        }

        return redirect()
            ->route('dashboard.categories.show', $category)
            ->with('success', 'Category permissions updated successfully');
    }
}
