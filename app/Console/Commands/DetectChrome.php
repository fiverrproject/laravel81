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
            $this->warn('Hostinger often disables shell_exec — set CHROME_PATH manually after: ls -la /usr/bin/chromium*');
            $this->warn('If no Chromium exists, shared hosting cannot run Chrome PDF; use VPS or ask Hostinger support.');

            return 1;
        }
    }
}
