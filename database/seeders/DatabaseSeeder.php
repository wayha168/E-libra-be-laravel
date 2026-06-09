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
        ]);

        // Demo users (same password for testing): password
        // Use updateOrCreate to avoid duplicate email issues when reseeding.
        $seedUsers = [
            [
                'name' => 'Super Admin',
                'email' => 'superadmin@example.com',
                'role' => 'super_admin',
            ],
            [
                'name' => 'Admin',
                'email' => 'admin@example.com',
                'role' => 'admin',
            ],
            [
                'name' => 'Author',
                'email' => 'author@example.com',
                'role' => 'author',
            ],
            [
                'name' => 'User',
                'email' => 'user@example.com',
                'role' => 'user',
            ],
        ];

        foreach ($seedUsers as $u) {
            $roleId = Role::where('role', $u['role'])->value('id');

            User::updateOrCreate(
                ['email' => $u['email']],
                [
                    'name' => $u['name'],
                    'role_id' => $roleId,
                    // Keep existing password if user exists; factories generate a password by default.
                ]
            );
        }
    }
}
