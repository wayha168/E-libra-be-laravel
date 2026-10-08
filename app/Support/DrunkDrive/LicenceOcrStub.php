<?php

namespace App\Support\DrunkDrive;

use Illuminate\Http\UploadedFile;

/**
 * Optional OCR stub for licence scans.
 * Accepts the uploaded file metadata and returns a placeholder payload.
 * Replace with a real OCR provider later without changing the API shape.
 */
class LicenceOcrStub
{
    /**
     * @return array{provider: string, status: string, extracted: array<string, mixed|null>, note: string}
     */
    public static function extract(?UploadedFile $file, array $manualFields = []): array
    {
        if (!$file) {
            return [
                'provider' => 'stub',
                'status' => 'skipped',
                'extracted' => [],
                'note' => 'No licence scan uploaded; manual fields used when provided.',
            ];
        }

        return [
            'provider' => 'stub',
            'status' => 'pending_manual_review',
            'extracted' => [
                'licence_number' => $manualFields['licence_number'] ?? null,
                'licence_class' => $manualFields['licence_class'] ?? null,
                'licence_country' => $manualFields['licence_country'] ?? null,
                'licence_issued_at' => $manualFields['licence_issued_at'] ?? null,
                'licence_expires_at' => $manualFields['licence_expires_at'] ?? null,
                'filename' => $file->getClientOriginalName(),
                'mime' => $file->getClientMimeType(),
                'size_bytes' => $file->getSize(),
            ],
            'note' => 'OCR not run in this environment; scan stored and manual fields retained.',
        ];
    }
}
