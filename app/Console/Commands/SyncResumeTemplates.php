<?php

namespace App\Console\Commands;

use App\Services\ResumeTemplatesInstaller;
use Illuminate\Console\Command;

class SyncResumeTemplates extends Command
{
    protected $signature = 'resume:sync-templates {--force-zip : Overwrite HTML from resumeformat.zip}';

    protected $description = 'Install resume HTML from resumeformat.zip (matches design JPGs) into resources/resume-builder';

    public function handle(): int
    {
        $before = ResumeTemplatesInstaller::resumeTemplateCount();

        if ($this->option('force-zip') || $before < 35) {
            if (! ResumeTemplatesInstaller::installFromBundledZip()) {
                $this->warn('resumeformat.zip missing or invalid at resources/resume-builder/resumeformat.zip');
                ResumeTemplatesInstaller::ensureInstalled();
            } else {
                $this->info('Installed templates from resumeformat.zip');
            }
        } else {
            $this->info('Templates already present (use --force-zip to refresh from zip)');
        }

        $after = ResumeTemplatesInstaller::resumeTemplateCount();
        $this->info("Templates: {$before} → {$after}");
        $this->info('Path: '.resource_path('resume-builder'));
        $this->info('Design previews: compare PDF to JPG names in _resume-maker.zip (resume31.jpg = resume31.html)');

        return $after >= 35 ? 0 : 1;
    }
}
