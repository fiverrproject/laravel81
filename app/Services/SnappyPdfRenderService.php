<?php

namespace App\Services;

use Knp\Snappy\Pdf;
use RuntimeException;

/**
 * PDF from resources/resume-builder HTML — match resumeformat.zip / preview JPGs (wkhtmltopdf WebKit).
 */
class SnappyPdfRenderService
{
    public function savePdf(string $html, string $outputPath): void
    {
        $binary = WkhtmltopdfPathResolver::tryResolve();
        if ($binary === null) {
            throw new RuntimeException('wkhtmltopdf binary not found. Run composer install on the server.');
        }

        $dir = dirname($outputPath);
        if ($dir !== '' && ! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $html = ResumePrintHtmlNormalizer::apply($html);
        [$html, $imageFiles] = ResumeImageEmbedder::embed($html);

        $snappy = new Pdf($binary);
        $snappy->setTimeout(180);
        $snappy->setOptions([
            'page-size' => 'A4',
            'orientation' => 'Portrait',
            'margin-top' => '0',
            'margin-right' => '0',
            'margin-bottom' => '0',
            'margin-left' => '0',
            'encoding' => 'UTF-8',
            'enable-local-file-access' => true,
            // Screen CSS from resume*.html (same as browser preview / JPG designs)
            'print-media-type' => false,
            'no-stop-slow-scripts' => true,
            'javascript-delay' => '300',
            // Fit 800px-wide templates (resume8, etc.) onto A4 like Node/Puppeteer did
            'disable-smart-shrinking' => false,
            'viewport-size' => (string) config('services.wkhtmltopdf.viewport', '1240x1754'),
            'dpi' => '96',
            'image-dpi' => '96',
            'load-error-handling' => 'ignore',
            'load-media-error-handling' => 'ignore',
        ]);

        try {
            $snappy->generateFromHtml($html, $outputPath, [], true);
        } finally {
            ResumeImageEmbedder::cleanup($imageFiles);
        }

        if (! is_file($outputPath) || filesize($outputPath) === 0) {
            throw new RuntimeException('wkhtmltopdf did not write: '.$outputPath);
        }
    }
}
