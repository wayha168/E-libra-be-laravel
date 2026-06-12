<?php

namespace App\Http\Controllers\View;

use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class CategoryController
{
    use AuthorizesRequests;


    public function index(Request $request): View
    {
        $this->authorize('viewAny', Category::class);

        $query = Category::query();

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where('name', 'like', "%{$search}%");
        }

        $categories = $query
            ->with(['image', 'bannerImage'])
            ->latest()
            ->paginate(10);


        return view('dashboard.categories.index', compact('categories'));
    }

    public function create(): View
    {
        $this->authorize('create', Category::class);

        return view('dashboard.categories.create');
    }

    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        $this->authorize('create', Category::class);

        $data = $request->validated();
        Category::create($data);

        return redirect()->route('dashboard.categories.index')->with('success', 'Category created successfully');
    }

    public function show(Category $category): View
    {
        $this->authorize('view', $category);

        return view('dashboard.categories.show', compact('category'));
    }

    public function edit(Category $category): View
    {
        $this->authorize('update', $category);

        return view('dashboard.categories.edit', compact('category'));
    }

    public function update(UpdateCategoryRequest $request, Category $category): RedirectResponse
    {
        $this->authorize('update', $category);

        $data = $request->validated();
        $category->update($data);

        return redirect()->route('dashboard.categories.index')->with('success', 'Category updated successfully');
    }

    public function destroy(Category $category): RedirectResponse
    {
        $this->authorize('delete', $category);

        $category->delete();

        return redirect()->route('dashboard.categories.index')->with('success', 'Category deleted successfully');
    }
}
