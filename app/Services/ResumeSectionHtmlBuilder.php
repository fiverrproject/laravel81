<?php

namespace App\Services;

/**
 * Builds resume section HTML in PHP (no Handlebars loops).
 * Fixes server PDFs where LightnCandy {{#each}}/{{#if}} hide Postman JSON fields.
 */
class ResumeSectionHtmlBuilder
{
    public const VERSION = 'resume-section-html-v1';

    /**
     * @param  array<string, mixed>  $data  Mapped template data (after ResumeTemplateDataMapper)
     * @return array<string, string>
     */
    public static function injectResume12Sections(array $data): array
    {
        return [
            'Resume12EducationHtml' => self::educationResume12($data),
            'Resume12ExperienceHtml' => self::experienceResume12($data),
            'Resume12ProjectsHtml' => self::projectsResume12($data),
            'Resume12AchievementHtml' => self::achievementResume12($data),
            'Resume12ReferenceHtml' => self::referenceResume12($data),
            'Resume12SkillsHtml' => self::skillsResume12($data),
            'Resume12LanguageHtml' => self::languageResume12($data),
            'Resume12InterestsHtml' => self::interestsResume12($data),
            'section_html_version' => self::VERSION,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function educationResume12(array $data): string
    {
        $rows = self::list($data['Education'] ?? null);
        if ($rows === []) {
            return '';
        }

        $html = '<div><h2 class="pl-10">Education</h2>';
        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }
            $course = self::str($row['course'] ?? $row['degree'] ?? $row['qualification'] ?? '');
            $institute = self::str($row['institute'] ?? $row['institution'] ?? $row['university'] ?? $row['college'] ?? '');
            $year = self::str($row['yearOfPassing'] ?? $row['year'] ?? '');
            if ($year === '') {
                $start = self::str($row['startYear'] ?? $row['from'] ?? '');
                $end = self::str($row['endYear'] ?? $row['to'] ?? '');
                $year = trim($start.(($start && $end) ? ' - ' : '').$end);
            }
            $pct = self::str($row['percentage'] ?? '');
            if ($pct === '' && isset($row['grade'])) {
                $g = self::str($row['grade']);
                if ($g !== '' && ! is_numeric(rtrim($g, '%'))) {
                    $institute = trim($institute.($institute !== '' ? ' — ' : '').$g);
                } elseif ($g !== '') {
                    $pct = rtrim($g, '%');
                }
            }

            if ($course === '' && $institute === '' && $year === '') {
                continue;
            }

            $html .= '<p class="m-0 pl-20 word-break"><b>'.self::e($course).'</b></p>';
            $html .= '<ul style="padding-left: 22px; margin-top: 0px; margin-bottom: 0px; padding-right: 5px;">';
            if ($institute !== '') {
                $html .= self::bullet($institute);
            }
            if ($pct !== '' && is_numeric($pct)) {
                $html .= self::bullet($pct.'%');
            }
            if ($year !== '') {
                $html .= self::bullet($year);
            }
            $html .= '</ul>';
        }
        $html .= '</div>';

        return $html;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function experienceResume12(array $data): string
    {
        $rows = self::list($data['Experience'] ?? null);
        if ($rows === []) {
            return '';
        }

        $html = '<div><h2 class="pl-10">Experience</h2>';
        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }
            $title = self::str($row['jobTitle'] ?? $row['designation'] ?? $row['position'] ?? '');
            $org = self::str($row['organization'] ?? $row['company'] ?? $row['companyName'] ?? '');
            $from = self::str($row['from'] ?? $row['startDate'] ?? $row['start'] ?? '');
            $to = self::str($row['to'] ?? $row['endDate'] ?? $row['end'] ?? '');
            $desc = self::str($row['description'] ?? $row['responsibilities'] ?? '');
            $role = self::str($row['role'] ?? '');
            $present = ! empty($row['present']) || strcasecmp($to, 'Present') === 0;
            if ($present) {
                $to = '';
            }

            $html .= '<p class="m-0 pl-20 word-break"><b>'.self::e($title).'</b></p>';
            $html .= '<ul style="padding-left: 22px; margin-top: 0px; margin-bottom: 0px; padding-right: 5px;">';
            if ($org !== '') {
                $html .= self::bullet($org);
            }
            if ($from !== '' || $to !== '') {
                $html .= self::bullet(trim($from.($to !== '' ? ' - '.$to : '')));
            } elseif ($present) {
                $html .= self::bullet('Present');
            }
            if ($role !== '') {
                $html .= self::bullet($role);
            }
            if ($desc !== '') {
                $html .= self::bullet($desc);
            }
            $html .= '</ul>';
        }
        $html .= '</div>';

        return $html;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function projectsResume12(array $data): string
    {
        $rows = self::list($data['Projects'] ?? null);
        if ($rows === []) {
            return '';
        }

        $html = '<div><h2 class="pl-10">Projects</h2>';
        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }
            $title = self::str($row['projectTitle'] ?? $row['title'] ?? $row['name'] ?? '');
            $role = self::str($row['roles'] ?? $row['role'] ?? '');
            $duration = self::str($row['duration'] ?? '');
            $desc = self::str($row['projectDescription'] ?? $row['description'] ?? '');
            if ($title === '' && $desc === '') {
                continue;
            }
            $html .= '<p class="m-0 pl-20 word-break"><b>'.self::e($title).'</b></p>';
            $html .= '<ul style="padding-left: 22px; margin-top: 0px; margin-bottom: 0px; padding-right: 5px;">';
            if ($role !== '') {
                $html .= self::bullet($role);
            }
            if ($duration !== '') {
                $html .= self::bullet($duration);
            }
            if ($desc !== '') {
                $html .= self::bullet($desc);
            }
            $html .= '</ul>';
        }
        $html .= '</div>';

        return $html;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function achievementResume12(array $data): string
    {
        $rows = self::list($data['Achivement'] ?? $data['Achievement'] ?? null);
        if ($rows === []) {
            return '';
        }

        $html = '<div><h2 class="pl-10">Achievement</h2>';
        foreach ($rows as $row) {
            $text = '';
            if (is_string($row)) {
                $text = trim($row);
            } elseif (is_array($row)) {
                $text = self::str($row['achivement'] ?? $row['achievement'] ?? '');
                if ($text === '') {
                    $text = trim(self::str($row['title'] ?? '').((self::str($row['title'] ?? '') && self::str($row['description'] ?? '')) ? ' - ' : '').self::str($row['description'] ?? ''));
                }
            }
            if ($text === '') {
                continue;
            }
            $html .= '<ul style="padding-left: 22px; margin-top: 0px; margin-bottom: 0px; padding-right: 5px;">';
            $html .= self::bullet($text);
            $html .= '</ul>';
        }
        $html .= '</div>';

        return $html;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function referenceResume12(array $data): string
    {
        $rows = self::list($data['Reference'] ?? null);
        if ($rows === []) {
            return '';
        }

        $html = '<div><h2 class="pl-10">Reference</h2>';
        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }
            $name = self::str($row['name'] ?? '');
            $job = self::str($row['jobtitle'] ?? $row['jobTitle'] ?? $row['designation'] ?? '');
            $company = self::str($row['companyname'] ?? $row['company'] ?? '');
            $email = self::str($row['email'] ?? '');
            $phone = self::str($row['phone'] ?? '');
            $contact = self::str($row['contact'] ?? '');
            if ($email === '' && str_contains($contact, '@')) {
                $email = $contact;
            } elseif ($phone === '' && $contact !== '') {
                $phone = $contact;
            }

            $html .= '<p class="m-0 pl-20 word-break"><b>'.self::e($name).'</b></p>';
            $html .= '<ul style="padding-left: 22px; margin-top: 0px; margin-bottom: 0px; padding-right: 5px;">';
            if ($job !== '') {
                $html .= self::bullet($job);
            }
            if ($company !== '') {
                $html .= self::bullet($company);
            }
            if ($email !== '') {
                $html .= self::bullet($email);
            }
            if ($phone !== '') {
                $html .= self::bullet($phone);
            }
            $html .= '</ul>';
        }
        $html .= '</div>';

        return $html;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function skillsResume12(array $data): string
    {
        $rows = self::list($data['Skills'] ?? null);
        if ($rows === []) {
            return '';
        }

        $html = '<div style="padding-bottom: 15px;"><h2 style="text-align: center;padding-top: 30px;"><u>Skills</u></h2>';
        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }
            $name = self::str($row['skillName'] ?? $row['name'] ?? $row['skill'] ?? '');
            if ($name === '') {
                continue;
            }
            $exp = $row['expertise'] ?? $row['level'] ?? 0;
            if ($exp === '' || $exp === null) {
                $exp = 0;
            }
            $expInt = is_numeric($exp) ? (int) $exp : 0;

            $html .= '<p class="m-0 mb-5 pl-10">'.self::e($name).'</p>';
            $html .= '<div class="progress" style="width: 93%; margin-left: 10px;">';
            $html .= '<div class="progress-bar bg-danger" style="width:'.$expInt.'%; color: white;">'.$expInt.'%</div>';
            $html .= '</div>';
        }
        $html .= '</div>';

        return $html;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function languageResume12(array $data): string
    {
        $rows = self::list($data['Language'] ?? null);
        if ($rows === []) {
            return '';
        }

        $html = '<div class="box-4"><h2 style="text-align: center;"><u>Language</u></h2>';
        foreach ($rows as $row) {
            $label = '';
            if (is_string($row)) {
                $label = trim($row);
            } elseif (is_array($row)) {
                $label = self::str($row['name'] ?? '');
                if ($label === '') {
                    $lang = self::str($row['language'] ?? $row['lang'] ?? '');
                    $level = self::str($row['level'] ?? $row['proficiency'] ?? '');
                    $label = $level !== '' ? $lang.' ('.$level.')' : $lang;
                }
            }
            if ($label === '') {
                continue;
            }
            $html .= '<ul style="padding-left: 10px; margin-top: 0px; margin-bottom: 0px; padding-right: 5px;">';
            $html .= '<li class="showPosts"><span class="greekCross" style="color: white;">✴</span>';
            $html .= '<p class="m-0 word-break">'.self::e($label).'</p></li></ul>';
        }
        $html .= '</div>';

        return $html;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function interestsResume12(array $data): string
    {
        $rows = self::list($data['Interests'] ?? null);
        if ($rows === []) {
            return '';
        }

        $html = '<div class="box-5"><h2 style="text-align: center;padding-top: 30px;"><u>Interests</u></h2>';
        foreach ($rows as $row) {
            $label = '';
            if (is_string($row)) {
                $label = trim($row);
            } elseif (is_array($row)) {
                $label = self::str($row['hoby'] ?? $row['hobby'] ?? $row['interest'] ?? $row['name'] ?? '');
            }
            if ($label === '') {
                continue;
            }
            $html .= '<ul style="padding-left: 10px; margin-top: 0px; margin-bottom: 0px; padding-right: 5px;">';
            $html .= '<li class="showPosts"><span class="greekCross" style="color: white;">✴</span>';
            $html .= '<p class="m-0 word-break">'.self::e($label).'</p></li></ul>';
        }
        $html .= '</div>';

        return $html;
    }

    protected static function bullet(string $text): string
    {
        return '<li class="showPosts"><span class="greekCross">✴</span><p class="m-0 word-break">'.self::e($text).'</p></li>';
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
            if (is_array($decoded)) {
                $value = $decoded;
            } else {
                return [];
            }
        }
        if (! is_array($value)) {
            return [];
        }

        return array_values($value);
    }

    protected static function str(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        return trim((string) $value);
    }

    protected static function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
