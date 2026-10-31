<?php

namespace App\Console\Commands;

use App\Services\ChromePathResolver;
use Illuminate\Console\Command;
use Throwable;

class DetectChrome extends Command
{
    protected $signature = 'resume:detect-chrome';

    protected $description = 'Find Chrome/Chromium path for PDF generation (run on Hostinger SSH)';

    public function handle(): int
    {
        $this->line('CHROME_PATH from config: '.(config('services.chrome.path') ?: '(empty)'));

        try {
            ['path' => $path, 'tried' => $tried] = ChromePathResolver::resolve();
            $this->info('OK — use this in .env:');
            $this->line('CHROME_PATH='.$path);
            $this->line('PDF_ENGINE=chrome');
            $this->line('Tried: '.implode(', ', $tried));

            return 0;
        } catch (Throwable $e) {
            $this->warn($e->getMessage());
            $this->info('No Chrome — use pure PHP PDF (recommended on Hostinger):');
            $this->line('PDF_ENGINE=dompdf');
            $this->line('Then: php artisan config:clear');

            return 0;
        }
    }
}
