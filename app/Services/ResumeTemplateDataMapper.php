<?php

namespace App\Services;

/**
 * Maps Postman / frontend JSON into Handlebars template keys for all resume*.html files.
 * Runs on every PDF render so live cannot skip mapping (Node used raw JSON — that caused blank Skills/Education).
 */
class ResumeTemplateDataMapper
{
    public const VERSION = 'laravel-resume-mapper-v7';

    /**
     * Full pipeline: raw API payload → template-ready array.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public static function fromApiPayload(array $payload): array
    {
        $normalized = (new ResumeDataNormalizer)->normalize($payload, 'Personal Details');

        return self::map($normalized);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function map(array $data): array
    {
        $decode = function ($v) {
            if (is_string($v)) {
                $trim = trim($v);
                if ($trim === '') {
                    return [];
                }
                $d = json_decode($trim, true);

                return is_array($d) ? array_values($d) : [];
            }

            return is_array($v) ? array_values($v) : [];
        };

        $skills = [];
        foreach ($decode($data['Skills'] ?? []) as $row) {
            if (! is_array($row)) {
                continue;
            }
            $name = (string) ($row['skillName'] ?? $row['name'] ?? $row['skill'] ?? '');
            if ($name === '') {
                continue;
            }
            $exp = $row['expertise'] ?? 0;
            if ($exp === '' || $exp === null) {
                $exp = 0;
            }
            $skills[] = [
                'skillName' => $name,
                'name' => $name,
                'expertise' => is_numeric($exp) ? (int) $exp : 0,
            ];
        }
        $data['Skills'] = $skills !== [] ? $skills : '';

        $education = [];
        foreach ($decode($data['Education'] ?? []) as $row) {
            if (! is_array($row)) {
                continue;
            }
            $course = (string) ($row['course'] ?? $row['degree'] ?? $row['qualification'] ?? '');
            $institute = (string) ($row['institute'] ?? $row['institution'] ?? $row['university'] ?? $row['college'] ?? '');
            $year = (string) ($row['yearOfPassing'] ?? $row['year'] ?? '');
            if ($year === '') {
                $year = trim(($row['startYear'] ?? '').((($row['startYear'] ?? '') && ($row['endYear'] ?? '')) ? ' - ' : '').($row['endYear'] ?? ''));
            }
            $percentage = $row['percentage'] ?? $row['grade'] ?? $row['score'] ?? '';
            if (is_string($percentage)) {
                $percentage = rtrim(trim($percentage), '%');
            }
            if ($percentage !== '' && ! is_numeric($percentage)) {
                $institute = trim($institute.($institute !== '' ? ' — ' : '').$percentage);
                $percentage = '';
            }
            if ($course === '' && $institute === '') {
                continue;
            }
            $education[] = [
                'course' => $course,
                'degree' => $course,
                'institute' => $institute,
                'institution' => $institute,
                'yearOfPassing' => $year,
                'startYear' => (string) ($row['startYear'] ?? ''),
                'endYear' => (string) ($row['endYear'] ?? ''),
                'percentage' => (string) $percentage,
                'grade' => (string) ($row['grade'] ?? ''),
            ];
        }
        $data['Education'] = $education !== [] ? $education : '';

        $experience = [];
        foreach ($decode($data['Experience'] ?? []) as $row) {
            if (! is_array($row)) {
                continue;
            }
            $to = $row['to'] ?? $row['endDate'] ?? $row['end'] ?? '';
            $present = ! empty($row['present']) || (is_string($to) && strcasecmp(trim((string) $to), 'Present') === 0);
            if ($present) {
                $to = '';
            }
            $jobTitle = (string) ($row['jobTitle'] ?? $row['designation'] ?? $row['position'] ?? '');
            $organization = (string) ($row['organization'] ?? $row['company'] ?? $row['companyName'] ?? '');
            $experience[] = [
                'jobTitle' => $jobTitle,
                'designation' => (string) ($row['designation'] ?? $jobTitle),
                'organization' => $organization,
                'company' => (string) ($row['company'] ?? $organization),
                'from' => (string) ($row['from'] ?? $row['startDate'] ?? $row['start'] ?? ''),
                'to' => (string) $to,
                'startDate' => (string) ($row['startDate'] ?? $row['from'] ?? ''),
                'endDate' => (string) ($row['endDate'] ?? $row['to'] ?? ''),
                'present' => $present ? true : '',
                'role' => (string) ($row['role'] ?? ''),
                'description' => (string) ($row['description'] ?? $row['responsibilities'] ?? ''),
                'location' => (string) ($row['location'] ?? ''),
            ];
        }
        $data['Experience'] = $experience !== [] ? $experience : '';

        $projects = [];
        foreach ($decode($data['Projects'] ?? []) as $row) {
            if (! is_array($row)) {
                continue;
            }
            $title = (string) ($row['projectTitle'] ?? $row['title'] ?? $row['name'] ?? '');
            if ($title === '') {
                continue;
            }
            $projects[] = [
                'projectTitle' => $title,
                'title' => $title,
                'roles' => (string) ($row['roles'] ?? $row['role'] ?? ''),
                'role' => (string) ($row['role'] ?? $row['roles'] ?? ''),
                'duration' => (string) ($row['duration'] ?? ''),
                'projectDescription' => (string) ($row['projectDescription'] ?? $row['description'] ?? ''),
                'description' => (string) ($row['description'] ?? $row['projectDescription'] ?? ''),
            ];
        }
        $data['Projects'] = $projects !== [] ? $projects : '';

        $reference = [];
        foreach ($decode($data['Reference'] ?? []) as $row) {
            if (! is_array($row)) {
                continue;
            }
            $contact = (string) ($row['contact'] ?? '');
            $email = (string) ($row['email'] ?? '');
            $phone = (string) ($row['phone'] ?? '');
            if ($contact !== '' && str_contains($contact, '@') && $email === '') {
                $email = $contact;
            } elseif ($contact !== '' && $phone === '') {
                $phone = $contact;
            }
            $reference[] = [
                'name' => (string) ($row['name'] ?? ''),
                'jobtitle' => (string) ($row['jobtitle'] ?? $row['jobTitle'] ?? $row['designation'] ?? ''),
                'designation' => (string) ($row['designation'] ?? $row['jobtitle'] ?? ''),
                'companyname' => (string) ($row['companyname'] ?? $row['company'] ?? ''),
                'company' => (string) ($row['company'] ?? $row['companyname'] ?? ''),
                'email' => $email,
                'contact' => $contact,
                'phone' => $phone,
            ];
        }
        $data['Reference'] = $reference !== [] ? $reference : '';

        $language = [];
        foreach ($decode($data['Language'] ?? []) as $row) {
            if (is_string($row) && trim($row) !== '') {
                $language[] = ['name' => trim($row), 'language' => trim($row)];
                continue;
            }
            if (! is_array($row)) {
                continue;
            }
            $name = (string) ($row['name'] ?? $row['language'] ?? $row['lang'] ?? '');
            $level = (string) ($row['level'] ?? $row['proficiency'] ?? '');
            if ($name === '') {
                continue;
            }
            $full = $level !== '' ? $name.' ('.$level.')' : $name;
            $language[] = ['name' => $full, 'language' => $name, 'level' => $level];
        }
        $data['Language'] = $language !== [] ? $language : '';

        $achivement = [];
        foreach ($decode($data['Achivement'] ?? $data['Achievement'] ?? $data['Achievements'] ?? []) as $row) {
            if (is_string($row) && trim($row) !== '') {
                $achivement[] = ['achivement' => trim($row)];
                continue;
            }
            if (! is_array($row)) {
                continue;
            }
            $text = (string) ($row['achivement'] ?? $row['achievement'] ?? '');
            if ($text === '') {
                $text = trim(($row['title'] ?? '').((($row['title'] ?? '') && ($row['description'] ?? '')) ? ' - ' : '').($row['description'] ?? ''));
            }
            if ($text !== '') {
                $achivement[] = [
                    'achivement' => $text,
                    'achievement' => $text,
                    'title' => (string) ($row['title'] ?? ''),
                    'description' => (string) ($row['description'] ?? ''),
                ];
            }
        }
        $data['Achivement'] = $achivement !== [] ? $achivement : '';

        $interests = [];
        foreach ($decode($data['Interests'] ?? []) as $row) {
            if (is_string($row) && trim($row) !== '') {
                $interests[] = ['hoby' => trim($row), 'hobby' => trim($row)];
                continue;
            }
            if (! is_array($row)) {
                continue;
            }
            $hoby = (string) ($row['hoby'] ?? $row['hobby'] ?? $row['name'] ?? $row['interest'] ?? '');
            if ($hoby !== '') {
                $interests[] = ['hoby' => $hoby, 'hobby' => $hoby];
            }
        }
        $data['Interests'] = $interests !== [] ? $interests : '';

        return $data;
    }
}
