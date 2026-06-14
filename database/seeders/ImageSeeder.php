<?php

namespace Database\Seeders;

use App\Models\Image;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ImageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Note:
        // - `images.url` is stored as a string.
        // - For local environments, you can replace these URLs with real public paths.
        // - Using picsum.me to provide deterministic-ish placeholder images.

        $seed = [
            ['url' => 'https://picsum.photos/id/1011/800/600', 'alt_text' => 'Cover Image', 'image_type' => 'cover'],
            ['url' => 'https://picsum.photos/id/1012/800/600', 'alt_text' => 'Programming Banner', 'image_type' => 'banner'],
            ['url' => 'https://picsum.photos/id/1013/800/600', 'alt_text' => 'Science Cover', 'image_type' => 'cover'],
            ['url' => 'https://picsum.photos/id/1014/800/600', 'alt_text' => 'Fiction Banner', 'image_type' => 'banner'],
            ['url' => 'https://picsum.photos/id/1015/800/600', 'alt_text' => 'Laravel Cover', 'image_type' => 'cover'],
            ['url' => 'https://picsum.photos/id/1016/800/600', 'alt_text' => 'JavaScript Cover', 'image_type' => 'cover'],
            ['url' => 'https://picsum.photos/id/1017/800/600', 'alt_text' => 'Author Profile 1', 'image_type' => 'author_profile'],
            ['url' => 'https://picsum.photos/id/1018/800/600', 'alt_text' => 'Author Profile 2', 'image_type' => 'author_profile'],
            ['url' => 'https://picsum.photos/id/1019/800/600', 'alt_text' => 'Author Profile 3', 'image_type' => 'author_profile'],
        ];

        foreach ($seed as $item) {
            Image::updateOrCreate(
                ['url' => $item['url']],
                [
                    'alt_text' => $item['alt_text'] ?? null,
                    'image_type' => $item['image_type'] ?? null,
                ]
            );
        }
    }
}
