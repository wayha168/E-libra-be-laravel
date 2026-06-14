<?php

namespace App\Support;

use setasign\Fpdi\Fpdi;

class BookPdfPreviewGenerator
{
    public static function generate(string $sourcePath, string $destPath, int $maxPages): bool
    {
        if (!is_readable($sourcePath) || $maxPages < 1) {
            return false;
        }

        try {
            $pdf = new Fpdi();
            $pageCount = $pdf->setSourceFile($sourcePath);
            $pages = min(max(1, $pageCount), $maxPages);

            for ($pageNo = 1; $pageNo <= $pages; $pageNo++) {
                $templateId = $pdf->importPage($pageNo);
                $size = $pdf->getTemplateSize($templateId);
                $orientation = ($size['width'] ?? 0) > ($size['height'] ?? 0) ? 'L' : 'P';
                $pdf->AddPage($orientation, [$size['width'], $size['height']]);
                $pdf->useTemplate($templateId);
            }

            $dir = dirname($destPath);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            $pdf->Output('F', $destPath);

            return is_readable($destPath);
        } catch (\Throwable) {
            if (is_file($destPath)) {
                @unlink($destPath);
            }

            return false;
        }
    }
}
