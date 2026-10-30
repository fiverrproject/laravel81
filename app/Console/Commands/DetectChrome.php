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
            $this->line('Tried: '.implode(', ', $tried));

            return 0;
        } catch (Throwable $e) {
            $this->error($e->getMessage());
            $this->warn('Hostinger shared plans often have NO Chrome. Options: VPS, or ask support for Chromium path.');

            return 1;
        }
    }
}
