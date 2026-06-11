<?php

namespace App\Http\Controllers\View;

use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CategoryController
{
    public function index(Request $request): View
    {
        $query = Category::query();

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where('name', 'like', "%{$search}%");
        }

        $categories = $query->latest()->paginate(10);

        return view('dashboard.categories.index', compact('categories'));
    }

    public function create(): View
    {
        return view('dashboard.categories.create');
    }

    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        $data = $request->validated();
        Category::create($data);

        return redirect()->route('dashboard.categories.index')->with('success', 'Category created successfully');
    }

    public function show(Category $category): View
    {
        return view('dashboard.categories.show', compact('category'));
    }

    public function edit(Category $category): View
    {
        return view('dashboard.categories.edit', compact('category'));
    }

    public function update(UpdateCategoryRequest $request, Category $category): RedirectResponse
    {
        $data = $request->validated();
        $category->update($data);

        return redirect()->route('dashboard.categories.index')->with('success', 'Category updated successfully');
    }

    public function destroy(Category $category): RedirectResponse
    {
        $category->delete();

        return redirect()->route('dashboard.categories.index')->with('success', 'Category deleted successfully');
    }
}
