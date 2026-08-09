<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Controller;
use App\Http\Requests\StoreImageRequest;
use App\Http\Requests\UpdateImageRequest;
use App\Models\Image;
use App\Support\StoresUploadedImages;
use Illuminate\Http\Request;

class ImageController extends Controller
{
    public function index(Request $request)
    {
        $query = Image::query();

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(function ($q) use ($search) {
                $q->where('url', 'like', "%{$search}%")
                    ->orWhere('alt_text', 'like', "%{$search}%");
            });
        }

        return response()->json([
            'message' => 'Images fetched successfully',
            'data' => $query->latest()->paginate(10),
        ]);
    }

    public function store(StoreImageRequest $request)
    {
        $data = $request->validated();

        if ($request->hasFile('image_file')) {
            $id = StoresUploadedImages::store(
                $request->file('image_file'),
                $data['image_type'] ?? 'general',
                $data['alt_text'] ?? null
            );
            $image = Image::query()->findOrFail($id);
        } else {
            $image = Image::create([
                'url' => $data['url'],
                'alt_text' => $data['alt_text'] ?? null,
                'image_type' => $data['image_type'] ?? null,
            ]);
        }

        return response()->json([
            'message' => 'Image created successfully',
            'data' => $image,
        ], 201);
    }

    public function show(Image $image)
    {
        return response()->json([
            'message' => 'Image fetched successfully',
            'data' => $image,
        ]);
    }

    public function update(UpdateImageRequest $request, Image $image)
    {
        $data = $request->validated();

        if ($request->hasFile('image_file')) {
            StoresUploadedImages::replaceFile(
                $image,
                $request->file('image_file'),
                $data['image_type'] ?? $image->image_type ?? 'general',
                $data['alt_text'] ?? null
            );
            $image->refresh();
        } else {
            $payload = [
                'alt_text' => $data['alt_text'] ?? $image->alt_text,
                'image_type' => $data['image_type'] ?? $image->image_type,
            ];
            if (! empty($data['url'])) {
                $payload['url'] = $data['url'];
            }
            $image->update($payload);
        }

        return response()->json([
            'message' => 'Image updated successfully',
            'data' => $image->fresh(),
        ]);
    }

    public function destroy(Image $image)
    {
        StoresUploadedImages::deleteById($image->id);

        return response()->json([
            'message' => 'Image deleted successfully',
            'data' => null,
        ]);
    }
}
