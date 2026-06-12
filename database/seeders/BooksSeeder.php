<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BooksSeeder extends Seeder
{

    public function run(): void
    {
        $categoryIds = \App\Models\Category::query()->pluck('id')->values()->all();
        $authorIds = \App\Models\Author::query()->pluck('id')->values()->all();
        $imageIds = \App\Models\Image::query()->pluck('id')->values()->all();

        // Ensure we don't seed books with null author_id (your UI expects an author relation).
        // If authors are missing, don't create invalid book rows.
        if (count($authorIds) === 0) {
            return;
        }

        $firstAuthorId = $authorIds[0];
        $firstCategoryId = $categoryIds[0] ?? null;
        $firstImageId = $imageIds[0] ?? null;

        $authorsCount = count($authorIds);
        $categoriesCount = count($categoryIds);
        $imagesCount = count($imageIds);

        for ($i = 1; $i <= 25; $i++) {
            $authorId = $authorIds[$i % $authorsCount] ?? $firstAuthorId;
            $categoryId = $categoriesCount > 0 ? ($categoryIds[$i % $categoriesCount] ?? $firstCategoryId) : null;
            $imageId = $imagesCount > 0 ? ($imageIds[$i % $imagesCount] ?? $firstImageId) : null;

            \App\Models\Books::query()->create([
                'title' => 'Book ' . $i,
                'description' => 'Description for book ' . $i,
                'author_id' => $authorId,
                'category_id' => $categoryId,
                'image_id' => $imageId,
                // public_date is nullable
                'public_date' => $i % 3 === 0 ? null : now()->subDays($i)->toDateString(),
            ]);
        }
    }
}
