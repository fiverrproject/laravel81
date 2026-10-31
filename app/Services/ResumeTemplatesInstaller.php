<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use ZipArchive;

/**
 * Ensures resume HTML matches bundled resumeformat.zip (design source of truth).
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
     * Extract resumeformat/resume*.html from resources/resume-builder/resumeformat.zip
     */
    public static function installFromBundledZip(): bool
    {
        $zipPath = resource_path('resume-builder/resumeformat.zip');
        if (! is_file($zipPath)) {
            return false;
        }

        $zip = new ZipArchive;
        if ($zip->open($zipPath) !== true) {
            return false;
        }

        $dest = resource_path('resume-builder');
        File::ensureDirectoryExists($dest);

        $written = 0;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (! is_string($name) || ! preg_match('#^resumeformat/((?:resume\d+|latest|professional)\.html)$#', $name, $m)) {
                continue;
            }
            $content = $zip->getFromIndex($i);
            if (! is_string($content) || $content === '') {
                continue;
            }
            file_put_contents($dest.'/'.$m[1], $content);
            $written++;
        }

        $zip->close();

        return $written >= 35;
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

        if (self::installFromBundledZip()) {
            return self::resumeTemplateCount() >= 35;
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
