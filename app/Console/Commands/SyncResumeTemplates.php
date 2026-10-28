<?php

namespace App\Console\Commands;

use App\Services\ResumeTemplatesInstaller;
use Illuminate\Console\Command;

class SyncResumeTemplates extends Command
{
    protected $signature = 'resume:sync-templates';

    protected $description = 'Copy resume*.html templates into resources/resume-builder (run on Hostinger after deploy)';

    public function handle(): int
    {
        $before = ResumeTemplatesInstaller::resumeTemplateCount();
        $ok = ResumeTemplatesInstaller::ensureInstalled();
        $after = ResumeTemplatesInstaller::resumeTemplateCount();

        $this->info("Templates before: {$before}, after: {$after}");
        $this->info('Path: '.resource_path('resume-builder'));

        if (! $ok) {
            $this->error('Could not find source templates. Upload resume-builder/ to resources/resume-builder/ or set RESUME_TEMPLATES_PATH in .env');

            return 1;
        }

        $this->info('OK');

        return 0;
    }
}
