<?php

namespace App\Services;

/**
 * Ensures resume12 PDF HTML contains PHP-built sections even when the server
 * still has the OLD resume12.html (Handlebars {{#if Education}} / {{#each Skills}}).
 */
class Resume12PostRenderFix
{
    public const VERSION = 'resume12-post-render-v3';

    /**
     * @param  array<string, mixed>  $data  Mapped data (after ResumeTemplateDataMapper)
     * @param  array<string, mixed>  $payload  Raw API payload (optional, re-map if needed)
     */
    public static function apply(string $html, array $data, array $payload = []): string
    {
        if ($payload !== []) {
            $data = ResumeTemplateDataMapper::fromApiPayload($payload);
        }

        $sections = ResumeSectionHtmlBuilder::injectResume12Sections($data);

        foreach ($sections as $key => $fragment) {
            if ($key === 'section_html_version' || ! str_ends_with($key, 'Html')) {
                continue;
            }
            $html = str_replace('{{{'.$key.'}}}', (string) $fragment, $html);
        }

        $main = $sections['Resume12EducationHtml']
            .$sections['Resume12ExperienceHtml']
            .$sections['Resume12ProjectsHtml']
            .$sections['Resume12AchievementHtml']
            .$sections['Resume12ReferenceHtml'];

        $sidebar = $sections['Resume12SkillsHtml']
            .$sections['Resume12LanguageHtml']
            .$sections['Resume12InterestsHtml'];

        if (self::needsMainColumnFix($html, $data)) {
            $html = self::replaceLegacyMainColumn($html, $main);
        }

        if (self::needsSidebarFix($html, $data)) {
            $html = self::replaceLegacySidebar($html, $sidebar);
        }

        $html = self::forceInjectRenderedTemplate($html, $main, $sidebar, $data);

        if (! str_contains($html, 'resume12-php-sections-v1')) {
            $html = str_replace('</body>', '<!-- resume12-php-sections-v1 post-fix --></body>', $html);
        }

        return $html;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected static function needsMainColumnFix(string $html, array $data): bool
    {
        if ($main = self::list($data['Education'] ?? null)) {
            if (! self::htmlShowsEducation($html, $main)) {
                return true;
            }
        }
        if ($exp = self::list($data['Experience'] ?? null)) {
            $first = is_array($exp[0] ?? null) ? $exp[0] : [];
            $title = (string) ($first['jobTitle'] ?? $first['designation'] ?? '');
            if ($title !== '' && ! str_contains($html, $title)) {
                return true;
            }
        }

        return str_contains($html, '{{#if Education}}') || str_contains($html, '{{#each Education}}');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected static function needsSidebarFix(string $html, array $data): bool
    {
        $skills = self::list($data['Skills'] ?? null);
        if ($skills !== []) {
            $first = is_array($skills[0] ?? null) ? $skills[0] : [];
            $name = (string) ($first['skillName'] ?? $first['name'] ?? '');
            if ($name !== '' && ! str_contains($html, $name)) {
                return true;
            }
        }

        return str_contains($html, '{{#if Skills}}') || str_contains($html, '{{#each Skills}}');
    }

    protected static function replaceLegacyMainColumn(string $html, string $mainHtml): string
    {
        if ($mainHtml === '') {
            return $html;
        }

        // Old template: everything from {{#if Education}} through {{#if Reference}}…{{/if}} before Signature.
        $pattern = '/\{\{#if Education\}\}[\s\S]*?(?=\{\{#if sign\}\}|<div style="display: flex; flex-direction: column; align-items: flex-end)/u';
        if (preg_match($pattern, $html)) {
            return (string) preg_replace($pattern, $mainHtml, $html, 1);
        }

        // Insert after Objective block (old or new template).
        $pattern2 = '/(\{\{#if Objective\}\}[\s\S]*?<\/div>\s*\{\{\/if\}\})/u';
        if (preg_match($pattern2, $html)) {
            return (string) preg_replace($pattern2, '$1'.$mainHtml, $html, 1);
        }

        // After PHP marker line (placeholders empty on disk).
        if (str_contains($html, 'resume12-php-sections-v1')) {
            return (string) preg_replace(
                '/<!-- resume12-php-sections-v1 -->/u',
                $mainHtml.'<!-- resume12-php-sections-v1 -->',
                $html,
                1
            );
        }

        return $html;
    }

    protected static function replaceLegacySidebar(string $html, string $sidebarHtml): string
    {
        if ($sidebarHtml === '') {
            return $html;
        }

        $pattern = '/\{\{#if Skills\}\}[\s\S]*?\{\{\/if\}\}(\s*\{\{#if Language\}\}[\s\S]*?\{\{\/if\}\})?(\s*\{\{#if Interests\}\}[\s\S]*?\{\{\/if\}\})?/u';
        if (preg_match($pattern, $html)) {
            return (string) preg_replace($pattern, $sidebarHtml, $html, 1);
        }

        // Empty Skills header only (sidebar title with no progress bars).
        if (preg_match('/<u>Skills<\/u>[\s\S]*?<u>Language<\/u>/u', $html) && ! str_contains($html, 'progress-bar')) {
            return (string) preg_replace(
                '/<div style="padding-bottom: 15px;">\s*<h2 style="text-align: center;padding-top: 30px;"><u>Skills<\/u><\/h2>[\s\S]*?(?=<div class="box-4">|<\/div>\s*<\/td>)/u',
                $sidebarHtml,
                $html,
                1
            );
        }

        return $html;
    }

    /**
     * Fix HTML after Handlebars compiled away {{#if}} (legacy resume12 on live server).
     *
     * @param  array<string, mixed>  $data
     */
    protected static function forceInjectRenderedTemplate(string $html, string $mainHtml, string $sidebarHtml, array $data): string
    {
        if ($mainHtml !== '' && preg_match('/<h2 class="pl-10">Education<\/h2>\s*<h2 class="pl-10">Experience<\/h2>/u', $html)) {
            $html = (string) preg_replace(
                '/(<h2 class="pl-10">Education<\/h2>)([\s\S]*?)(<div style="display: flex; flex-direction: column; align-items: flex-end|<h2 class="pl-10">Signature<\/h2>)/u',
                $mainHtml.'$3',
                $html,
                1
            );
        }

        if ($mainHtml !== '' && str_contains($html, '<h2 class="pl-10">Education</h2>')) {
            $edu = self::list($data['Education'] ?? null);
            if ($edu !== [] && ! self::htmlShowsEducation($html, $edu)) {
                $html = (string) preg_replace(
                    '/(<h2 class="pl-10">Education<\/h2>)([\s\S]*?)(<div style="display: flex; flex-direction: column; align-items: flex-end|<h2 class="pl-10">Signature<\/h2>)/u',
                    $mainHtml.'$3',
                    $html,
                    1
                );
            }
        }

        if ($mainHtml !== '') {
            $exp = self::list($data['Experience'] ?? null);
            $first = is_array($exp[0] ?? null) ? $exp[0] : [];
            $title = (string) ($first['jobTitle'] ?? $first['designation'] ?? '');
            if ($title !== '' && ! str_contains($html, $title)) {
                $html = (string) preg_replace(
                    '/(<h2 class="pl-10">Education<\/h2>[\s\S]*?)(<div style="display: flex; flex-direction: column; align-items: flex-end|<h2 class="pl-10">Signature<\/h2>)/u',
                    $mainHtml.'$2',
                    $html,
                    1
                );
            }
        }

        if ($sidebarHtml !== '' && self::needsSidebarForce($html, $data)) {
            $html = (string) preg_replace(
                '/(<h2 style="text-align: center;padding-top: 30px;"><u>Skills<\/u><\/h2>)([\s\S]*?)(<td style="width: 3\.9%)/u',
                $sidebarHtml.'$3',
                $html,
                1
            );
        }

        return $html;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected static function needsSidebarForce(string $html, array $data): bool
    {
        if (! str_contains($html, '<u>Skills</u>')) {
            return false;
        }

        foreach (self::list($data['Skills'] ?? null) as $row) {
            if (! is_array($row)) {
                continue;
            }
            $name = (string) ($row['skillName'] ?? $row['name'] ?? '');
            if ($name !== '' && ! str_contains($html, $name)) {
                return true;
            }
        }

        foreach (self::list($data['Language'] ?? null) as $row) {
            $label = self::languageLabel($row);
            if ($label !== '' && ! str_contains($html, $label)) {
                return true;
            }
        }

        foreach (self::list($data['Interests'] ?? null) as $row) {
            $label = is_string($row) ? trim($row) : (string) ($row['hoby'] ?? $row['hobby'] ?? '');
            if ($label !== '' && ! str_contains($html, $label)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  mixed  $row
     */
    protected static function languageLabel($row): string
    {
        if (is_string($row)) {
            return trim($row);
        }
        if (! is_array($row)) {
            return '';
        }
        $name = (string) ($row['name'] ?? '');
        if ($name !== '') {
            return $name;
        }
        $lang = (string) ($row['language'] ?? '');
        $level = (string) ($row['level'] ?? '');

        return $level !== '' ? $lang.' ('.$level.')' : $lang;
    }

    /**
     * @param  array<int, mixed>  $educationRows
     */
    protected static function htmlShowsEducation(string $html, array $educationRows): bool
    {
        foreach ($educationRows as $row) {
            if (! is_array($row)) {
                continue;
            }
            $course = (string) ($row['course'] ?? $row['degree'] ?? '');
            if ($course !== '' && str_contains($html, $course)) {
                return true;
            }
            $inst = (string) ($row['institute'] ?? $row['institution'] ?? '');
            if ($inst !== '' && str_contains($html, $inst)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  mixed  $value
     * @return array<int, mixed>
     */
    protected static function list($value): array
    {
        if ($value === null || $value === '') {
            return [];
        }
        if (is_string($value)) {
            $decoded = json_decode(trim($value), true);

            return is_array($decoded) ? array_values($decoded) : [];
        }

        return is_array($value) ? array_values($value) : [];
    }
}
