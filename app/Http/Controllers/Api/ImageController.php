<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Controller;
use App\Http\Requests\StoreImageRequest;
use App\Http\Requests\UpdateImageRequest;
use App\Models\Image;
use Illuminate\Http\Request;

class ImageController extends Controller
{
    public function index(Request $request)
    {
        $query = Image::query();

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where('path', 'like', "%{$search}%")
                ->orWhere('alt', 'like', "%{$search}%");
        }

        return response()->json([
            'message' => 'Images fetched successfully',
            'data' => $query->latest()->paginate(10),
        ]);
    }

    public function store(StoreImageRequest $request)
    {
        $image = Image::create($request->validated());

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
        $image->update($request->validated());

        return response()->json([
            'message' => 'Image updated successfully',
            'data' => $image,
        ]);
    }

    public function destroy(Image $image)
    {
        $image->delete();

        return response()->json([
            'message' => 'Image deleted successfully',
            'data' => null,
        ]);
    }
}
