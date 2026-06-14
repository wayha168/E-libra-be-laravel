<?php

namespace App\Support;

use App\Models\Image;
use App\Models\Books;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class StoresUploadedImages
{
    public static function store(?UploadedFile $file, string $type = 'general', ?string $alt = null): ?string
    {
        if (!$file) {
            return null;
        }

        $path = $file->store('uploads/' . $type, 'public');

        $image = Image::create([
            'url' => Storage::disk('public')->url($path),
            'alt_text' => $alt ?? $file->getClientOriginalName(),
            'image_type' => $type,
        ]);

        return $image->id;
    }

    /**
     * @return list<string> Image UUIDs
     */
    public static function storeMany(array $files, string $type = 'general', ?string $alt = null): array
    {
        $ids = [];

        foreach ($files as $file) {
            if (!$file instanceof UploadedFile) {
                continue;
            }

            $id = self::store($file, $type, $alt);
            if ($id) {
                $ids[] = $id;
            }
        }

        return $ids;
    }

    public static function attachToBook(Books $book, array $imageIds): void
    {
        $imageIds = array_values(array_filter($imageIds));
        if ($imageIds === []) {
            return;
        }

        $startOrder = (int) ($book->images()->max('sort_order') ?? 0);

        foreach ($imageIds as $offset => $imageId) {
            if ($book->images()->where('images.id', $imageId)->exists()) {
                continue;
            }

            $book->images()->attach($imageId, [
                'id' => (string) Str::uuid(),
                'sort_order' => $startOrder + $offset + 1,
            ]);
        }

        if (!$book->image_id) {
            $book->update(['image_id' => $imageIds[0]]);
        }
    }

    public static function replaceFile(Image $image, UploadedFile $file, string $type = 'general', ?string $alt = null): void
    {
        $relativePath = self::relativePathFromUrl($image->url);
        if ($relativePath && Storage::disk('public')->exists($relativePath)) {
            Storage::disk('public')->delete($relativePath);
        }

        $path = $file->store('uploads/' . $type, 'public');

        $image->update([
            'url' => Storage::disk('public')->url($path),
            'alt_text' => $alt ?? $file->getClientOriginalName(),
            'image_type' => $type,
        ]);
    }

    public static function deleteById(?string $imageId): void
    {
        if (!$imageId) {
            return;
        }

        $image = Image::query()->find($imageId);
        if (!$image) {
            return;
        }

        $relativePath = self::relativePathFromUrl($image->url);
        if ($relativePath && Storage::disk('public')->exists($relativePath)) {
            Storage::disk('public')->delete($relativePath);
        }

        $image->delete();
    }

    private static function relativePathFromUrl(?string $url): ?string
    {
        if (!$url) {
            return null;
        }

        $storagePrefix = '/storage/';
        $position = strpos($url, $storagePrefix);

        if ($position === false) {
            return null;
        }

        return Str::after($url, $storagePrefix);
    }
}
