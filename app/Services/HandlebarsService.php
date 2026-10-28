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

        $data = $this->prepareTemplateData($data);

        $htmlData = file_get_contents($templatePath);

        if (self::usesResume12PhpSections($templatePath)) {
            $htmlData = $this->embedResume12PhpSections($htmlData, $data);
        }

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
        return $this->prepareTemplateData($data);
    }

    /**
     * Guarantee template keys (skillName, course, organization, …) even if an old controller or cached deploy skipped mapping.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function prepareTemplateData(array $data): array
    {
        if (isset($data['Personal Details']) || isset($data['personal_details'])) {
            return ResumeTemplateDataMapper::fromApiPayload($data);
        }

        if ($this->payloadLooksUnmapped($data)) {
            return ResumeTemplateDataMapper::fromApiPayload(
                $this->rebuildApiPayloadFromFlat($data)
            );
        }

        return ResumeTemplateDataMapper::map($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function payloadLooksUnmapped(array $data): bool
    {
        if (is_array($data['Skills'] ?? null) && isset($data['Skills'][0]) && is_array($data['Skills'][0])) {
            $first = $data['Skills'][0];
            if (isset($first['name']) && ($first['skillName'] ?? '') === '') {
                return true;
            }
        }

        if (is_array($data['Education'] ?? null) && isset($data['Education'][0]) && is_array($data['Education'][0])) {
            $first = $data['Education'][0];
            if ((isset($first['degree']) || isset($first['institution'])) && ($first['course'] ?? '') === '') {
                return true;
            }
        }

        if (is_array($data['Language'] ?? null) && isset($data['Language'][0])) {
            $first = $data['Language'][0];
            if (is_array($first) && isset($first['language']) && ($first['name'] ?? '') === '') {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function rebuildApiPayloadFromFlat(array $data): array
    {
        return [
            'format' => $data['format'] ?? 'resume31.html',
            'Personal Details' => [
                'Name' => $data['Name'] ?? '',
                'Address' => $data['Address'] ?? '',
                'Email' => $data['Email'] ?? '',
                'Phone' => $data['Phone'] ?? '',
                'DOB' => $data['DOB'] ?? '',
                'jobTitle' => $data['jobTitle'] ?? '',
            ],
            'Objective' => ['Objective' => $data['Objective'] ?? ''],
            'Signature' => ['sign' => $data['sign'] ?? ''],
            'profilePhoto' => $data['profilePhoto'] ?? '',
            'Skills' => $data['Skills'] ?? [],
            'Education' => $data['Education'] ?? [],
            'Experience' => $data['Experience'] ?? [],
            'Projects' => $data['Projects'] ?? [],
            'Reference' => $data['Reference'] ?? [],
            'Language' => $data['Language'] ?? [],
            'Achivement' => $data['Achivement'] ?? [],
            'Interests' => $data['Interests'] ?? [],
        ];
    }

    protected static function usesResume12PhpSections(string $templatePath): bool
    {
        return str_ends_with(str_replace('\\', '/', $templatePath), 'resume12.html');
    }

    /**
     * Replace {{{Resume12*Html}}} placeholders with PHP-built HTML before LightnCandy runs.
     *
     * @param  array<string, mixed>  $data
     */
    protected function embedResume12PhpSections(string $htmlData, array $data): string
    {
        $sections = ResumeSectionHtmlBuilder::injectResume12Sections($data);
        foreach ($sections as $key => $fragment) {
            if ($key === 'section_html_version' || ! str_ends_with($key, 'Html')) {
                continue;
            }
            $htmlData = str_replace('{{{'.$key.'}}}', (string) $fragment, $htmlData);
        }

        return $htmlData;
    }
}
