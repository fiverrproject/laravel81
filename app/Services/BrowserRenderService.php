<?php

namespace App\Services;

use Spatie\Browsershot\Browsershot;

class BrowserRenderService
{
    protected function browser(string $html): Browsershot
    {
        $shot = Browsershot::html($html)
            ->noSandbox()
            ->ignoreHttpsErrors()
            ->timeout(120)
            ->setOption('args', [
                '--no-sandbox',
                '--disable-setuid-sandbox',
                '--disable-dev-shm-usage',
                '--disable-gpu',
                '--disable-software-rasterizer',
            ]);

        $chromePath = config('services.chrome.path');
        if (! empty($chromePath) && is_file($chromePath)) {
            $shot->setChromePath($chromePath);
        }

        $nodePath = config('services.chrome.node_path');
        $npmPath = config('services.chrome.npm_path');
        $nodeModules = config('services.chrome.node_modules_path');

        if ($nodePath) {
            $shot->setNodeBinary($nodePath);
        }

        if ($npmPath) {
            $shot->setNpmBinary($npmPath);
        }

        if ($nodeModules && is_dir($nodeModules)) {
            $shot->setNodeModulePath($nodeModules);
        }

        return $shot;
    }

    public function savePdf(string $html, string $outputPath, array $margins = ['top' => 0, 'right' => 0, 'bottom' => 0, 'left' => 0]): void
    {
        $this->ensureDirectory(dirname($outputPath));

        // Match Node Puppeteer: small/zero margins in px (Browsershot default unit is mm).
        // Also force @page margin:0 so Chrome does not add white top/bottom bands.
        $html = $this->withPdfPrintStyles($html);

        $this->browser($html)
            ->margins(
                $margins['top'] ?? 0,
                $margins['right'] ?? 0,
                $margins['bottom'] ?? 0,
                $margins['left'] ?? 0,
                'px'
            )
            ->format('A4')
            ->showBackground()
            ->savePdf($outputPath);
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

        $this->browser($html)
            ->windowSize($width, $height)
            ->deviceScaleFactor(1)
            ->setScreenshotType('jpeg', 90)
            ->save($outputPath);
    }

    protected function ensureDirectory(string $dir): void
    {
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }
}
