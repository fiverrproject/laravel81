<?php

namespace App\Services;

use HeadlessChromium\BrowserFactory;
use HeadlessChromium\Exception\OperationTimedOut;
use RuntimeException;

/**
 * HTML → PDF/JPEG via headless Chrome (pure PHP chrome-php/chrome — no Node/npm).
 */
class BrowserRenderService
{
    /**
     * @return array<string, mixed>
     */
    protected function browserOptions(int $width = 1920, int $height = 1080): array
    {
        $chromePath = config('services.chrome.path');
        if (! empty($chromePath) && ! is_file($chromePath)) {
            throw new RuntimeException("Chrome not found at {$chromePath}. Set CHROME_PATH in .env");
        }

        return [
            'headless' => true,
            'noSandbox' => true,
            'windowSize' => [$width, $height],
            'customFlags' => [
                '--disable-dev-shm-usage',
                '--disable-gpu',
                '--disable-software-rasterizer',
            ],
            'sendSyncDefaultTimeout' => 120000,
            'startupTimeout' => 60,
        ];
    }

    protected function createBrowser(int $width = 1920, int $height = 1080)
    {
        $chromePath = config('services.chrome.path');
        $factory = new BrowserFactory(
            ! empty($chromePath) && is_file($chromePath) ? (string) $chromePath : null
        );

        return $factory->createBrowser($this->browserOptions($width, $height));
    }

    public function savePdf(string $html, string $outputPath, array $margins = ['top' => 0, 'right' => 0, 'bottom' => 0, 'left' => 0]): void
    {
        $this->ensureDirectory(dirname($outputPath));

        $html = $this->withPdfPrintStyles($html);

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

    /**
     * Inject print CSS so resume backgrounds fill the page without white edges.
     */
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
  body {
    min-height: 100%;
  }
  body.canvas {
    min-height: 297mm;
    background-attachment: fixed;
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
