<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Validator;

class PdfUploadValidation
{
    public static function inspect(Request $request, Validator $validator, string $field = 'pdf_file'): void
    {
        $uploadMax = ini_get('upload_max_filesize') ?: '?';
        $postMax = ini_get('post_max_size') ?: '?';
        $contentLength = (int) $request->server('CONTENT_LENGTH', 0);
        $postMaxBytes = self::iniToBytes($postMax);

        // Entire multipart body discarded when over post_max_size
        if ($postMaxBytes > 0 && $contentLength > $postMaxBytes) {
            $validator->errors()->forget($field);
            $validator->errors()->add(
                $field,
                "PDF request too large ({$contentLength} bytes). Increase post_max_size (now {$postMax}) and nginx client_max_body_size."
            );

            return;
        }

        $file = $request->file($field);
        if (! $file instanceof UploadedFile) {
            return;
        }

        if ($file->isValid()) {
            return;
        }

        $validator->errors()->forget($field);
        $validator->errors()->add($field, self::message($file, $uploadMax, $postMax));
    }

    public static function message(UploadedFile $file, ?string $uploadMax = null, ?string $postMax = null): string
    {
        $uploadMax ??= ini_get('upload_max_filesize') ?: '?';
        $postMax ??= ini_get('post_max_size') ?: '?';
        $code = $file->getError();

        return match ($code) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => "PDF too large for PHP (upload_max_filesize={$uploadMax}, post_max_size={$postMax}). Set both to 512M/520M in php-fpm and reload.",
            UPLOAD_ERR_PARTIAL => 'PDF was only partially uploaded. Try again.',
            UPLOAD_ERR_NO_FILE => 'No PDF file was received.',
            UPLOAD_ERR_NO_TMP_DIR => 'Server missing temp folder for uploads (/tmp).',
            UPLOAD_ERR_CANT_WRITE => 'Server could not write the PDF (disk full or permissions).',
            UPLOAD_ERR_EXTENSION => 'A PHP extension blocked the PDF upload.',
            default => "The pdf file failed to upload (PHP error {$code}; upload_max_filesize={$uploadMax}, post_max_size={$postMax}).",
        };
    }

    public static function iniToBytes(string $value): int
    {
        $value = trim($value);
        if ($value === '' || $value === '0') {
            return 0;
        }

        $unit = strtolower(substr($value, -1));
        $number = (float) $value;

        return (int) match ($unit) {
            'g' => $number * 1024 * 1024 * 1024,
            'm' => $number * 1024 * 1024,
            'k' => $number * 1024,
            default => $number,
        };
    }
}
