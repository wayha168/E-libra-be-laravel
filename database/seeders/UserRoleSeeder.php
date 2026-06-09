<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class UserRoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            'super_admin',
            'admin',
            'author',
            'user',
        ];

        foreach ($roles as $roleName) {
            Role::updateOrCreate(
                ['role' => $roleName],
                ['role' => $roleName]
            );
        }
    }
}
