<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class StoresUploadedFiles
{
    public static function storePdf(?UploadedFile $file): ?string
    {
        if (!$file) {
            return null;
        }

        $path = $file->store('uploads/pdfs', 'public');
        return Storage::disk('public')->url($path);
    }
}