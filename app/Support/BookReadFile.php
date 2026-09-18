<?php

namespace App\Support;

use App\Models\Books;
use setasign\Fpdi\Fpdi;

/**
 * Builds a compact "read" copy of a book PDF that is small enough to store
 * inside the database (base64) for offline reading. All pages are re-imported
 * through FPDI, which normalises the document and strips incremental-update
 * bloat, and the result is only kept when it stays under the configured cap.
 */
class BookReadFile
{
    public static function maxKb(): int
    {
        return (int) config('elibra.book_read_max_kb', 4096);
    }

    /**
     * Generate the compact read copy for a source PDF.
     *
     * @return array{data: string, size: int}|null base64 data + byte size, or null when
     *                                              generation fails or exceeds the size cap.
     */
    public static function generate(string $sourcePath, ?int $maxKb = null): ?array
    {
        if (!is_readable($sourcePath)) {
            return null;
        }

        $maxBytes = max(1, ($maxKb ?? self::maxKb())) * 1024;
        $destPath = tempnam(sys_get_temp_dir(), 'book-read-') . '.pdf';

        try {
            $pdf = new Fpdi();
            $pageCount = $pdf->setSourceFile($sourcePath);

            for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                $templateId = $pdf->importPage($pageNo);
                $size = $pdf->getTemplateSize($templateId);
                $orientation = ($size['width'] ?? 0) > ($size['height'] ?? 0) ? 'L' : 'P';
                $pdf->AddPage($orientation, [$size['width'], $size['height']]);
                $pdf->useTemplate($templateId);
            }

            $pdf->Output('F', $destPath);

            if (!is_readable($destPath)) {
                return null;
            }

            $bytes = filesize($destPath);
            if ($bytes === false || $bytes <= 0 || $bytes > $maxBytes) {
                return null;
            }

            $contents = file_get_contents($destPath);
            if ($contents === false) {
                return null;
            }

            return [
                'data' => base64_encode($contents),
                'size' => $bytes,
            ];
        } catch (\Throwable) {
            return null;
        } finally {
            if (is_file($destPath)) {
                @unlink($destPath);
            }
        }
    }

    /** Whether the book has a compact read copy stored in the DB. */
    public static function has(Books $book): bool
    {
        return filled($book->pdf_read_data);
    }

    /** Decode the stored read copy back into raw PDF bytes. */
    public static function decode(Books $book): ?string
    {
        if (!filled($book->pdf_read_data)) {
            return null;
        }

        $decoded = base64_decode((string) $book->pdf_read_data, true);

        return $decoded === false ? null : $decoded;
    }
}
