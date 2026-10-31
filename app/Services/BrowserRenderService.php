<?php

namespace App\Services;

use HeadlessChromium\BrowserFactory;
use HeadlessChromium\Exception\OperationTimedOut;
use RuntimeException;

/**
 * PDF: wkhtmltopdf (Hostinger, layout like templates) → Chrome (VPS) → DomPDF (last resort).
 */
class BrowserRenderService
{
    public function __construct(
        protected DomPdfRenderService $domPdf,
        protected SnappyPdfRenderService $snappy
    ) {}

    protected ?string $resolvedChromePath = null;

    public function pdfEngine(): string
    {
        $configured = strtolower(trim((string) config('services.pdf.engine', 'auto')));

        return match ($configured) {
            'chrome' => ChromePathResolver::tryResolve() !== null ? 'chrome' : $this->fallbackEngine(),
            'wkhtmltopdf', 'snappy' => WkhtmltopdfPathResolver::tryResolve() !== null ? 'wkhtmltopdf' : $this->fallbackEngine(),
            'dompdf' => 'dompdf',
            default => $this->resolveAutoEngine(),
        };
    }

    protected function resolveAutoEngine(): string
    {
        if (ChromePathResolver::tryResolve() !== null) {
            return 'chrome';
        }
        if (WkhtmltopdfPathResolver::tryResolve() !== null) {
            return 'wkhtmltopdf';
        }

        return 'dompdf';
    }

    protected function fallbackEngine(): string
    {
        if (WkhtmltopdfPathResolver::tryResolve() !== null) {
            return 'wkhtmltopdf';
        }

        return 'dompdf';
    }

    protected function browserOptions(int $width = 1920, int $height = 1080): array
    {
        return [
            'headless' => true,
            'noSandbox' => true,
            'windowSize' => [$width, $height],
            'customFlags' => [
                '--disable-dev-shm-usage',
                '--disable-gpu',
                '--disable-software-rasterizer',
                '--single-process',
            ],
            'sendSyncDefaultTimeout' => 120000,
            'startupTimeout' => 90,
        ];
    }

    protected function chromeBinary(): string
    {
        if ($this->resolvedChromePath !== null) {
            return $this->resolvedChromePath;
        }

        $path = ChromePathResolver::tryResolve();
        if ($path === null) {
            throw new RuntimeException('Chrome not available.');
        }

        $this->resolvedChromePath = $path;

        return $this->resolvedChromePath;
    }

    protected function createBrowser(int $width = 1920, int $height = 1080)
    {
        $factory = new BrowserFactory($this->chromeBinary());

        return $factory->createBrowser($this->browserOptions($width, $height));
    }

    public function savePdf(string $html, string $outputPath, array $margins = ['top' => 0, 'right' => 0, 'bottom' => 0, 'left' => 0]): void
    {
        $engine = $this->pdfEngine();

        if ($engine === 'wkhtmltopdf') {
            $this->snappy->savePdf($html, $outputPath);

            return;
        }

        $html = ResumePrintHtmlNormalizer::apply($html);
        $html = $this->withPdfPrintStyles($html);

        if ($engine === 'dompdf') {
            $this->domPdf->savePdfWithoutNormalize($html, $outputPath);

            return;
        }

        $this->ensureDirectory(dirname($outputPath));

        $browser = $this->createBrowser();
        try {
            $page = $browser->createPage();
            $page->setHtml($html, 120000);

            $pxToIn = static fn ($px) => max(0.0, (float) $px / 96.0);

            $pdf = $page->pdf([
                'printBackground' => true,
                'preferCSSPageSize' => true,
                'marginTop' => $pxToIn($margins['top'] ?? 0),
                'marginRight' => $pxToIn($margins['right'] ?? 0),
                'marginBottom' => $pxToIn($margins['bottom'] ?? 0),
                'marginLeft' => $pxToIn($margins['left'] ?? 0),
                'paperWidth' => 8.27,
                'paperHeight' => 11.69,
            ]);

            $pdf->saveToFile($outputPath);

            if (! is_file($outputPath) || filesize($outputPath) === 0) {
                throw new RuntimeException('Chrome did not write PDF: '.$outputPath);
            }
        } finally {
            $browser->close();
        }
    }

    protected function withPdfPrintStyles(string $html): string
    {
        $styles = <<<'CSS'
<style id="laravel-pdf-print-fix">
  @page { margin: 0 !important; size: A4; }
  html, body {
    margin: 0 !important;
    padding: 0 !important;
    -webkit-print-color-adjust: exact !important;
    print-color-adjust: exact !important;
  }
</style>
CSS;

        if (stripos($html, '</head>') !== false) {
            return preg_replace('/<\/head>/i', $styles.'</head>', $html, 1) ?? ($styles.$html);
        }

        return $styles.$html;
    }

    public function saveScreenshot(string $html, string $outputPath, int $width, int $height): void
    {
        if (ChromePathResolver::tryResolve() === null) {
            throw new RuntimeException(
                'JPEG screenshots need Chrome on the server. Resume PDF uses wkhtmltopdf on Hostinger.'
            );
        }

        $this->ensureDirectory(dirname($outputPath));

        $browser = $this->createBrowser($width, $height);
        try {
            $page = $browser->createPage();
            $page->setHtml($html, 120000);

            $screenshot = $page->screenshot([
                'format' => 'jpeg',
                'quality' => 90,
                'captureBeyondViewport' => true,
            ]);

            $screenshot->saveToFile($outputPath);
        } catch (OperationTimedOut $e) {
            throw new RuntimeException('Screenshot timed out: '.$e->getMessage(), 0, $e);
        } finally {
            $browser->close();
        }
    }

    protected function ensureDirectory(string $dir): void
    {
        if ($dir !== '' && ! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }
}
