<?php

namespace Database\Seeders;

use App\Models\Author;
use App\Models\Image;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AuthorSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // Ensure we have images to reference.
        $imageIds = Image::query()->pluck('id')->values()->all();

        $authorRoleId = Role::where('role', 'author')->value('id');
        if (!$authorRoleId) {
            return;
        }

        $users = User::query()->where('role_id', $authorRoleId)->get();
        if ($users->isEmpty()) {
            return;
        }

        foreach ($users as $index => $user) {
            $imageId = $imageIds[$index % max(count($imageIds), 1)] ?? null;

            Author::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'image_id' => $imageId,
                    'bio' => 'Bio for ' . ($user->name ?? 'author'),
                ]
            );
        }
    }
}
