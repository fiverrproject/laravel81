<?php

namespace App\Services;

use LightnCandy\LightnCandy;
use RuntimeException;

class HandlebarsService
{
    public const MAPPER_VERSION = ResumeTemplateDataMapper::VERSION;

    /**
     * Compile a Handlebars HTML template with the given data.
     * Always remaps frontend field aliases right before render (live-safe).
     */
    public function render(string $templatePath, array $data): string
    {
        if (! is_file($templatePath)) {
            throw new RuntimeException("Template not found: {$templatePath}");
        }

        // Always map at render time (server must not use raw JSON like old Node).
        $data = ResumeTemplateDataMapper::map($data);

        $htmlData = file_get_contents($templatePath);

        $php = LightnCandy::compile($htmlData, [
            'flags' => LightnCandy::FLAG_HANDLEBARS
                | LightnCandy::FLAG_ERROR_EXCEPTION
                | LightnCandy::FLAG_RUNTIMEPARTIAL
                | LightnCandy::FLAG_BESTPERFORMANCE,
        ]);

        $renderer = LightnCandy::prepare($php);

        return $renderer($data);
    }

    /**
     * Resolve a safe template path under the resume-builder directory.
     */
    public function resolveTemplatePath(string $relativePath): string
    {
        $base = realpath(resource_path('resume-builder'));
        $full = realpath($base.DIRECTORY_SEPARATOR.str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relativePath));

        if ($base === false || $full === false || ! str_starts_with($full, $base)) {
            throw new RuntimeException('Invalid template path.');
        }

        return $full;
    }

    /**
     * Map frontend JSON keys to resume template keys used by all resume*.html files.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function applyTemplateAliases(array $data): array
    {
        return ResumeTemplateDataMapper::map($data);
    }
}
