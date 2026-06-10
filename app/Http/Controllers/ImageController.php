<?php

namespace App\Http\Controllers;

use App\Models\Image;
use App\Http\Requests\StoreImageRequest;
use App\Http\Requests\UpdateImageRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ImageController
{
    public function index(Request $request): View
    {
        $query = Image::query();

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where('path', 'like', "%{$search}%")
                ->orWhere('alt', 'like', "%{$search}%");
        }

        $images = $query->latest()->paginate(10);

        return view('dashboard.images.index', compact('images'));
    }

    public function create(): View
    {
        return view('dashboard.images.create');
    }

    public function store(StoreImageRequest $request): RedirectResponse
    {
        $data = $request->validated();
        Image::create($data);

        return redirect()->route('dashboard.images.index')->with('success', 'Image created successfully');
    }

    public function show(Image $image): View
    {
        return view('dashboard.images.show', compact('image'));
    }

    public function edit(Image $image): View
    {
        return view('dashboard.images.edit', compact('image'));
    }

    public function update(UpdateImageRequest $request, Image $image): RedirectResponse
    {
        $data = $request->validated();
        $image->update($data);

        return redirect()->route('dashboard.images.index')->with('success', 'Image updated successfully');
    }

    public function destroy(Image $image): RedirectResponse
    {
        $image->delete();

        return redirect()->route('dashboard.images.index')->with('success', 'Image deleted successfully');
    }
}
