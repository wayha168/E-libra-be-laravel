<?php

namespace App\Support;

use App\Models\Books;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BookPdfStorage
{
    private const DISK = 'local';

    private const DIR = 'books';

    public static function storeUpload(UploadedFile $file): array
    {
        $filename = Str::uuid() . '.pdf';
        $path = self::DIR . '/' . $filename;

        Storage::disk(self::DISK)->putFileAs(self::DIR, $file, $filename);

        $absolute = Storage::disk(self::DISK)->path($path);
        $previewPath = self::DIR . '/preview-' . $filename;

        if (BookPdfPreviewGenerator::generate($absolute, Storage::disk(self::DISK)->path($previewPath), BookAccess::trialPages())) {
            return [
                'pdf_file' => $path,
                'pdf_preview_path' => $previewPath,
            ];
        }

        return [
            'pdf_file' => $path,
            'pdf_preview_path' => null,
        ];
    }

    public static function resolveFullPath(Books $book): ?string
    {
        return self::resolvePath($book->pdf_file);
    }

    public static function resolvePreviewPath(Books $book): ?string
    {
        if ($book->pdf_preview_path) {
            $preview = self::resolvePath($book->pdf_preview_path);
            if ($preview) {
                return $preview;
            }
        }

        return self::resolveFullPath($book);
    }

    public static function resolvePath(?string $stored): ?string
    {
        if (!$stored) {
            return null;
        }

        if (str_starts_with($stored, 'http://') || str_starts_with($stored, 'https://')) {
            return null;
        }

        $relative = self::normalizeRelativePath($stored);

        if (Storage::disk(self::DISK)->exists($relative)) {
            return Storage::disk(self::DISK)->path($relative);
        }

        $publicRelative = self::publicRelativeFromLegacy($stored);
        if ($publicRelative && Storage::disk('public')->exists($publicRelative)) {
            return Storage::disk('public')->path($publicRelative);
        }

        return null;
    }

    public static function streamFile(string $absolutePath, string $downloadName, bool $inline = true): StreamedResponse
    {
        return response()->stream(function () use ($absolutePath) {
            $stream = fopen($absolutePath, 'rb');
            if ($stream) {
                fpassthru($stream);
                fclose($stream);
            }
        }, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => ($inline ? 'inline' : 'attachment') . '; filename="' . addslashes($downloadName) . '"',
            'Cache-Control' => 'private, no-store, no-cache, must-revalidate',
            'Pragma' => 'no-cache',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private static function normalizeRelativePath(string $stored): string
    {
        $path = ltrim($stored, '/');

        if (str_starts_with($path, 'storage/')) {
            $path = substr($path, strlen('storage/'));
        }

        return $path;
    }

    private static function publicRelativeFromLegacy(string $stored): ?string
    {
        $path = self::normalizeRelativePath($stored);

        if (str_starts_with($path, 'uploads/pdfs/')) {
            return $path;
        }

        if (str_contains($stored, '/storage/uploads/pdfs/')) {
            return 'uploads/pdfs/' . basename($stored);
        }

        return null;
    }
}
