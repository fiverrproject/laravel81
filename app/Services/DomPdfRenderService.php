<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;

/**
 * Last-resort PDF when wkhtmltopdf/Chrome unavailable (layout may differ from template).
 */
class DomPdfRenderService
{
    public function savePdfWithoutNormalize(string $html, string $outputPath): void
    {
        $dir = dirname($outputPath);
        if ($dir !== '' && ! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $pdf = Pdf::loadHTML($html);
        $pdf->setPaper('a4', 'portrait');

        $dompdf = $pdf->getDomPDF();
        $dompdf->set_option('isRemoteEnabled', true);
        $dompdf->set_option('isHtml5ParserEnabled', true);
        $dompdf->set_option('defaultFont', 'DejaVu Sans');
        $dompdf->set_option('dpi', 96);

        $pdf->save($outputPath);

        if (! is_file($outputPath) || filesize($outputPath) === 0) {
            throw new \RuntimeException('DomPDF did not write: '.$outputPath);
        }
    }
}
