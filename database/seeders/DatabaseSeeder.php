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
            ImageSeeder::class,
            CategorySeeder::class,
        ]);

        $this->seedDefaultUsers();

        $this->call([
            AuthorSeeder::class,
            BooksSeeder::class,
            TemplateDataSeeder::class,
        ]);
    }

    private function seedDefaultUsers(): void
    {
        $seedUsers = [
            ['name' => 'Super Admin', 'email' => 'superadmin@elibra.com', 'role' => 'super_admin'],
            ['name' => 'Admin', 'email' => 'admin@elibra.com', 'role' => 'admin'],
            ['name' => 'Author', 'email' => 'author@elibra.com', 'role' => 'author'],
            ['name' => 'Reader User', 'email' => 'user@elibra.com', 'role' => 'user'],
        ];

        foreach ($seedUsers as $u) {
            $roleId = Role::where('role', $u['role'])->value('id');

            User::updateOrCreate(
                ['email' => $u['email']],
                [
                    'name' => $u['name'],
                    'role_id' => $roleId,
                    'password' => bcrypt('password'),
                    'confirm_password' => bcrypt('password'),
                    'status' => 'active',
                    'user_subscribe' => false,
                ]
            );
        }
    }
}
