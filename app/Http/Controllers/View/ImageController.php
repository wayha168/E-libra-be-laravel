<?php

namespace App\Http\Controllers\View;

use App\Http\Requests\StoreImageRequest;
use App\Http\Requests\UpdateImageRequest;
use App\Models\Image;
use App\Support\StoresUploadedImages;
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
            $query->where(function ($q) use ($search) {
                $q->where('url', 'like', "%{$search}%")
                    ->orWhere('alt_text', 'like', "%{$search}%");
            });
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

        if ($request->hasFile('image_file')) {
            StoresUploadedImages::store(
                $request->file('image_file'),
                $data['image_type'] ?? 'general',
                $data['alt_text'] ?? null
            );
        } else {
            Image::create([
                'url' => $data['url'],
                'alt_text' => $data['alt_text'] ?? null,
                'image_type' => $data['image_type'] ?? null,
            ]);
        }

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

        $payload = [
            'alt_text' => $data['alt_text'] ?? $image->alt_text,
            'image_type' => $data['image_type'] ?? $image->image_type,
        ];

        if (!empty($data['url'])) {
            $payload['url'] = $data['url'];
        }

        if ($request->hasFile('image_file')) {
            StoresUploadedImages::replaceFile(
                $image,
                $request->file('image_file'),
                $data['image_type'] ?? 'general',
                $data['alt_text'] ?? null
            );
        } else {
            $image->update($payload);
        }

        return redirect()->route('dashboard.images.index')->with('success', 'Image updated successfully');
    }

    public function destroy(Image $image): RedirectResponse
    {
        StoresUploadedImages::deleteById($image->id);

        return redirect()->route('dashboard.images.index')->with('success', 'Image deleted successfully');
    }
}
