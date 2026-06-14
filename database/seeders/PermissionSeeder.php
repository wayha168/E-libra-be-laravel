<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            ['name' => 'view_users', 'display_name' => 'View Users', 'roles' => ['super_admin', 'admin']],
            ['name' => 'create_users', 'display_name' => 'Create Users', 'roles' => ['super_admin', 'admin']],
            ['name' => 'edit_users', 'display_name' => 'Edit Users', 'roles' => ['super_admin', 'admin']],
            ['name' => 'delete_users', 'display_name' => 'Delete Users', 'roles' => ['super_admin', 'admin']],

            ['name' => 'view_categories', 'display_name' => 'View Categories', 'roles' => ['super_admin', 'admin', 'author', 'user']],
            ['name' => 'create_categories', 'display_name' => 'Create Categories', 'roles' => ['super_admin', 'admin']],
            ['name' => 'edit_categories', 'display_name' => 'Edit Categories', 'roles' => ['super_admin', 'admin']],
            ['name' => 'delete_categories', 'display_name' => 'Delete Categories', 'roles' => ['super_admin', 'admin']],

            ['name' => 'view_books', 'display_name' => 'View Books', 'roles' => ['super_admin', 'admin', 'author', 'user']],
            ['name' => 'create_books', 'display_name' => 'Create Books', 'roles' => ['super_admin', 'admin', 'author']],
            ['name' => 'edit_books', 'display_name' => 'Edit Books', 'roles' => ['super_admin', 'admin', 'author']],
            ['name' => 'delete_books', 'display_name' => 'Delete Books', 'roles' => ['super_admin', 'admin', 'author']],

            ['name' => 'view_images', 'display_name' => 'View Images', 'roles' => ['super_admin', 'admin']],
            ['name' => 'create_images', 'display_name' => 'Create Images', 'roles' => ['super_admin', 'admin']],
            ['name' => 'edit_images', 'display_name' => 'Edit Images', 'roles' => ['super_admin', 'admin']],
            ['name' => 'delete_images', 'display_name' => 'Delete Images', 'roles' => ['super_admin', 'admin']],

            ['name' => 'manage_permissions', 'display_name' => 'Manage Permissions', 'roles' => ['super_admin', 'admin']],
        ];

        foreach ($permissions as $perm) {
            $permission = Permission::updateOrCreate(
                ['name' => $perm['name']],
                [
                    'display_name' => $perm['display_name'],
                    'description' => $this->getDescription($perm['name']),
                ]
            );

            $roleIds = Role::whereIn('role', $perm['roles'])->pluck('id')->toArray();
            $permission->roles()->detach();
            foreach ($roleIds as $roleId) {
                $permission->roles()->attach($roleId, ['id' => (string) Str::uuid()]);
            }
        }
    }

    private function getDescription(string $name): string
    {
        return match ($name) {
            'view_users' => 'View user list and details',
            'create_users' => 'Create new users',
            'edit_users' => 'Edit existing users',
            'delete_users' => 'Delete users',
            'view_categories' => 'View category list and details',
            'create_categories' => 'Create new categories',
            'edit_categories' => 'Edit existing categories',
            'delete_categories' => 'Delete categories',
            'view_books' => 'View book list and details',
            'create_books' => 'Create new books',
            'edit_books' => 'Edit existing books',
            'delete_books' => 'Delete books',
            'view_images' => 'View image list and details',
            'create_images' => 'Upload new images',
            'edit_images' => 'Edit existing images',
            'delete_images' => 'Delete images',
            'manage_permissions' => 'Manage role permissions',
            default => '',
        };
    }
}