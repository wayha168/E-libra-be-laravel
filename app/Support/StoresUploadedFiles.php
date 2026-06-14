<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;

class StoresUploadedFiles
{
    public static function storePdf(?UploadedFile $file): ?array
    {
        if (!$file) {
            return null;
        }

        return BookPdfStorage::storeUpload($file);
    }
}
