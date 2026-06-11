<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            UserRoleSeeder::class,
            PermissionSeeder::class,
        ]);

        $seedUsers = [
            [
                'name' => 'Super Admin',
                'email' => 'superadmin@elibra.com',
                'role' => 'super_admin',
                'password' => bcrypt('password'),
                'confirm_password' => bcrypt('password'),

            ],
            [
                'name' => 'Admin',
                'email' => 'admin@elibra.com',
                'role' => 'admin',
                'password' => bcrypt('password'),
                'confirm_password' => bcrypt('password'),

            ],
            [
                'name' => 'Author',
                'email' => 'author@elibra.com',
                'role' => 'author',
                'password' => bcrypt('password'),
                'confirm_password' => bcrypt('password'),
            ],
            [
                'name' => 'User',
                'email' => 'user@elibra.com',
                'role' => 'user',
                'password' => bcrypt('password'),
                'confirm_password' => bcrypt('password'),
            ],
        ];

        foreach ($seedUsers as $u) {
            $roleId = Role::where('role', $u['role'])->value('id');

            User::updateOrCreate(
                ['email' => $u['email']],
                [
                    'name' => $u['name'],
                    'role_id' => $roleId,
                    'password' => $u['password'],
                    'confirm_password' => $u['confirm_password'],
                ]
            );
        }
    }
}
