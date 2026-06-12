<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{

    public function run(): void
    {

        $tree = [
            [
                'name' => 'Books',
                'description' => 'All books in the library',
                'slug' => 'books',
                'children' => [
                    [
                        'name' => 'Programming',
                        'description' => 'Software development & programming topics',
                        'slug' => 'programming',
                        'children' => [
                            [
                                'name' => 'Laravel',
                                'description' => 'Laravel framework books',
                                'slug' => 'laravel',
                            ],
                            [
                                'name' => 'JavaScript',
                                'description' => 'JavaScript & ecosystem',
                                'slug' => 'javascript',
                            ],
                        ],
                    ],
                    [
                        'name' => 'Science',
                        'description' => 'Science and research',
                        'slug' => 'science',
                    ],
                    [
                        'name' => 'Fiction',
                        'description' => 'Fiction books',
                        'slug' => 'fiction',
                    ],
                ],
            ],
        ];

        $imageIds = \App\Models\Image::query()->pluck('id')->values()->all();
        $coverIds = array_values(array_filter($imageIds, function ($id, $i) {
            return $id && $i % 2 === 0;
        }, ARRAY_FILTER_USE_BOTH));
        $bannerIds = array_values(array_filter($imageIds, function ($id, $i) {
            return $id && $i % 2 === 1;
        }, ARRAY_FILTER_USE_BOTH));

        foreach ($tree as $root) {
            $this->seedNode($root, null, $coverIds, $bannerIds);
        }
    }

    private function seedNode(array $node, ?string $parentId, array $coverIds, array $bannerIds): void
    {
        $existingCount = \App\Models\Category::query()->count();
        $idx = $existingCount % max(count($coverIds), 1);
        $coverId = $coverIds[$idx] ?? null;
        $bannerId = $bannerIds[$idx % max(count($bannerIds), 1)] ?? null;

        $category = \App\Models\Category::query()->updateOrCreate(
            [
                'slug' => $node['slug'] ?? null,
                'parent_id' => $parentId,
            ],
            [
                'name' => $node['name'],
                'description' => $node['description'] ?? null,
                'slug' => $node['slug'] ?? null,
                'parent_id' => $parentId,
                'image_id' => $coverId,
                'banner_image_id' => $bannerId,
            ]
        );

        $children = $node['children'] ?? [];
        foreach ($children as $child) {
            $this->seedNode($child, $category->id, $coverIds, $bannerIds);
        }
    }
}
