<?php

namespace App\Services;

use Illuminate\Support\Facades\File;

/**
 * Ensures resume HTML templates exist under resources/resume-builder (often missing on server because of .gitignore).
 */
class ResumeTemplatesInstaller
{
    public static function resumeTemplateCount(): int
    {
        $dir = resource_path('resume-builder');
        if (! is_dir($dir)) {
            return 0;
        }

        return count(glob($dir.'/resume*.html') ?: []);
    }

    /**
     * Copy templates from fallback paths when the Laravel resources folder is empty or incomplete.
     */
    public static function ensureInstalled(): bool
    {
        $dest = resource_path('resume-builder');
        if (self::resumeTemplateCount() >= 35) {
            return true;
        }

        $sources = array_filter([
            env('RESUME_TEMPLATES_PATH'),
            base_path('../resume-builder'),
            dirname(base_path()).'/resume-builder',
        ]);

        foreach ($sources as $src) {
            if (! is_string($src) || ! is_dir($src)) {
                continue;
            }
            if (count(glob($src.'/resume*.html') ?: []) < 35) {
                continue;
            }

            File::ensureDirectoryExists($dest);
            File::copyDirectory($src, $dest);

            return self::resumeTemplateCount() >= 35;
        }

        return false;
    }
}
