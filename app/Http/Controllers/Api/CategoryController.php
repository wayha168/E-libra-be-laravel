<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Controller;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Models\Category;
use App\Support\StoresUploadedImages;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $query = Category::query()->withCount('books');

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where('name', 'like', "%{$search}%");
        }

        return response()->json([
            'message' => 'Categories fetched successfully',
            'data' => $query->latest()->paginate(10),
        ]);
    }

    public function store(StoreCategoryRequest $request)
    {
        $data = $request->validated();
        unset($data['image_file'], $data['banner_image_file']);

        if ($request->hasFile('image_file')) {
            $data['image_id'] = StoresUploadedImages::store(
                $request->file('image_file'),
                'category',
                $data['name'] ?? 'Category'
            );
        }

        if ($request->hasFile('banner_image_file')) {
            $data['banner_image_id'] = StoresUploadedImages::store(
                $request->file('banner_image_file'),
                'category_banner',
                ($data['name'] ?? 'Category').' banner'
            );
        }

        $category = Category::create($data);

        return response()->json([
            'message' => 'Category created successfully',
            'data' => $category->load(['image', 'bannerImage']),
        ], 201);
    }

    public function show(Category $category)
    {
        return response()->json([
            'message' => 'Category fetched successfully',
            'data' => $category->load(['image', 'bannerImage']),
        ]);
    }

    public function update(UpdateCategoryRequest $request, Category $category)
    {
        $data = $request->validated();
        unset($data['image_file'], $data['banner_image_file']);

        if ($request->hasFile('image_file')) {
            $data['image_id'] = StoresUploadedImages::store(
                $request->file('image_file'),
                'category',
                $data['name'] ?? $category->name
            );
        }

        if ($request->hasFile('banner_image_file')) {
            $data['banner_image_id'] = StoresUploadedImages::store(
                $request->file('banner_image_file'),
                'category_banner',
                ($data['name'] ?? $category->name).' banner'
            );
        }

        $category->update($data);

        return response()->json([
            'message' => 'Category updated successfully',
            'data' => $category->fresh()->load(['image', 'bannerImage']),
        ]);
    }

    public function destroy(Category $category)
    {
        $category->delete();

        return response()->json([
            'message' => 'Category deleted successfully',
            'data' => null,
        ]);
    }
}
