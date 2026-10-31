<?php

namespace App\Console\Commands;

use App\Services\BrowserRenderService;
use App\Services\ChromePathResolver;
use App\Services\WkhtmltopdfPathResolver;
use Illuminate\Console\Command;

class DetectPdfEngine extends Command
{
    protected $signature = 'resume:detect-pdf';

    protected $description = 'Show which PDF engine will render resources/resume-builder templates';

    public function handle(BrowserRenderService $browser): int
    {
        $this->info('PDF_ENGINE config: '.(config('services.pdf.engine') ?: 'auto'));
        $this->info('Selected engine: '.$browser->pdfEngine());

        if ($path = WkhtmltopdfPathResolver::tryResolve()) {
            $this->line('wkhtmltopdf: '.$path);
        } else {
            $this->warn('wkhtmltopdf: not found (run composer install with h4cc/wkhtmltopdf-amd64)');
        }

        if ($path = ChromePathResolver::tryResolve()) {
            $this->line('chrome: '.$path);
        } else {
            $this->line('chrome: not found');
        }

        if ($browser->pdfEngine() === 'wkhtmltopdf') {
            $this->info('OK — PDF should match HTML templates in resources/resume-builder/');
        } elseif ($browser->pdfEngine() === 'dompdf') {
            $this->warn('Using DomPDF — layout will NOT match templates. Install wkhtmltopdf via composer on server.');
        }

        return 0;
    }
}
