<?php

namespace App\Support;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Support\Str;

class SyncsRolePermissions
{
    /**
     * @param  list<string>  $roleIds
     */
    public static function syncRolesForPermission(Permission $permission, array $roleIds): void
    {
        $permission->roles()->detach();

        foreach (array_values(array_unique($roleIds)) as $roleId) {
            $permission->roles()->attach($roleId, ['id' => (string) Str::uuid()]);
        }
    }

    /**
     * @param  list<string>  $permissionIds
     */
    public static function syncPermissionsForRole(Role $role, array $permissionIds): void
    {
        $role->permissions()->detach();

        foreach (array_values(array_unique($permissionIds)) as $permissionId) {
            $role->permissions()->attach($permissionId, ['id' => (string) Str::uuid()]);
        }
    }
}
