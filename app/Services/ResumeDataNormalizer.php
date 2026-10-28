<?php

namespace App\Services;

/**
 * Maps client-friendly JSON field names to the Handlebars template keys
 * used by all resume*.html templates (course, skillName, hoby, etc.).
 */
class ResumeDataNormalizer
{
    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function normalize(array $payload, string $personalKey = 'Personal Details'): array
    {
        // Some frontends wrap payload or send JSON-string fields.
        if (isset($payload['data']) && is_array($payload['data']) && ! isset($payload['Personal Details']) && ! isset($payload['format'])) {
            $payload = $payload['data'];
        }

        $personal = $this->asArray($payload[$personalKey] ?? $payload['personal_details'] ?? []);
        $objective = '';
        $objectiveBlock = $this->asArray($payload['Objective'] ?? []);
        if (isset($objectiveBlock['Objective'])) {
            $objective = (string) $objectiveBlock['Objective'];
        } elseif (isset($payload['Objective']) && is_string($payload['Objective'])) {
            $objective = $payload['Objective'];
        }

        $sign = '';
        $signatureBlock = $this->asArray($payload['Signature'] ?? []);
        if (isset($signatureBlock['sign'])) {
            $sign = (string) $signatureBlock['sign'];
        } elseif (isset($payload['sign'])) {
            $sign = (string) $payload['sign'];
        }

        $skills = $this->normalizeSkills($payload['Skills'] ?? $payload['skills'] ?? []);
        $education = $this->normalizeEducation($payload['Education'] ?? $payload['education'] ?? []);
        $experience = $this->normalizeExperience($payload['Experience'] ?? $payload['experience'] ?? []);
        $projects = $this->normalizeProjects($payload['Projects'] ?? $payload['projects'] ?? []);
        $reference = $this->normalizeReference($payload['Reference'] ?? $payload['reference'] ?? []);
        $language = $this->normalizeLanguage($payload['Language'] ?? $payload['Languages'] ?? $payload['language'] ?? []);
        $achivement = $this->normalizeAchivement($payload['Achivement'] ?? $payload['Achievement'] ?? $payload['Achievements'] ?? []);
        $interests = $this->normalizeInterests($payload['Interests'] ?? $payload['Hobby'] ?? $payload['Hobbies'] ?? []);

        return [
            'Name' => $personal['Name'] ?? $personal['name'] ?? '',
            'Address' => $personal['Address'] ?? $personal['address'] ?? '',
            'Email' => $personal['Email'] ?? $personal['email'] ?? '',
            'Phone' => $personal['Phone'] ?? $personal['phone'] ?? '',
            'DOB' => $personal['DOB'] ?? $personal['dob'] ?? '',
            'jobTitle' => $personal['jobTitle'] ?? $personal['JobTitle'] ?? $personal['job_title'] ?? '',
            'Education' => $education !== [] ? $education : '',
            'Experience' => $experience !== [] ? $experience : '',
            'Skills' => $skills !== [] ? $skills : '',
            'Reference' => $reference !== [] ? $reference : '',
            'Projects' => $projects !== [] ? $projects : '',
            'Language' => $language !== [] ? $language : '',
            'Achivement' => $achivement !== [] ? $achivement : '',
            'Interests' => $interests !== [] ? $interests : '',
            'sign' => $sign,
            'Objective' => $objective,
            'profilePhoto' => $payload['profilePhoto'] ?? $payload['profile_photo'] ?? '',
        ];
    }

    /**
     * @param  mixed  $items
     * @return array<int, array<string, mixed>>
     */
    protected function normalizeSkills($items): array
    {
        $out = [];
        foreach ($this->asList($items) as $skill) {
            if (! is_array($skill)) {
                continue;
            }
            if ($this->isEmptyRow($skill)) {
                continue;
            }

            $expertise = $skill['expertise'] ?? $skill['level'] ?? 0;
            if ($expertise === '' || $expertise === null || $expertise === '0') {
                $expertise = 0;
            }
            $expertise = is_numeric($expertise) ? (int) $expertise : 0;

            $out[] = [
                'skillName' => (string) ($skill['skillName'] ?? $skill['name'] ?? $skill['skill'] ?? ''),
                'expertise' => $expertise,
            ];
        }

        return array_values(array_filter($out, fn ($s) => $s['skillName'] !== ''));
    }

    /**
     * @param  mixed  $items
     * @return array<int, array<string, mixed>>
     */
    protected function normalizeEducation($items): array
    {
        $out = [];
        foreach ($this->asList($items) as $row) {
            if (! is_array($row) || $this->isEmptyRow($row)) {
                continue;
            }

            $year = $row['yearOfPassing'] ?? $row['year'] ?? null;
            if ($year === null || $year === '') {
                $start = $row['startYear'] ?? $row['from'] ?? '';
                $end = $row['endYear'] ?? $row['to'] ?? '';
                $year = trim($start.(($start && $end) ? ' - ' : '').$end);
            }

            $percentage = $row['percentage'] ?? $row['grade'] ?? $row['score'] ?? '';
            if (is_string($percentage)) {
                $percentage = rtrim(trim($percentage), '%');
            }

            $institute = (string) ($row['institute'] ?? $row['institution'] ?? $row['university'] ?? $row['college'] ?? '');

            // Templates always render "{{percentage}}%" — only use percentage for numeric scores.
            // Text grades like "3.8 GPA" are appended to institute instead.
            if ($percentage !== '' && ! is_numeric($percentage)) {
                $institute = trim($institute.($institute !== '' ? ' — ' : '').$percentage);
                $percentage = '';
            }

            $out[] = [
                'course' => (string) ($row['course'] ?? $row['degree'] ?? $row['qualification'] ?? ''),
                'institute' => $institute,
                'yearOfPassing' => (string) $year,
                'percentage' => (string) $percentage,
            ];
        }

        return $out;
    }

    /**
     * @param  mixed  $items
     * @return array<int, array<string, mixed>>
     */
    protected function normalizeExperience($items): array
    {
        $out = [];
        foreach ($this->asList($items) as $row) {
            if (! is_array($row) || $this->isEmptyRow($row)) {
                continue;
            }

            $to = $row['to'] ?? $row['endDate'] ?? $row['end'] ?? '';
            $present = $row['present'] ?? null;
            if ($present === null && is_string($to) && strcasecmp(trim($to), 'Present') === 0) {
                $present = true;
                $to = '';
            }

            $out[] = [
                'jobTitle' => (string) ($row['jobTitle'] ?? $row['designation'] ?? $row['position'] ?? $row['title'] ?? ''),
                'organization' => (string) ($row['organization'] ?? $row['company'] ?? $row['companyName'] ?? ''),
                'from' => (string) ($row['from'] ?? $row['startDate'] ?? $row['start'] ?? ''),
                'to' => (string) $to,
                'present' => $present ? true : '',
                'role' => (string) ($row['role'] ?? ''),
                'description' => (string) ($row['description'] ?? $row['responsibilities'] ?? ''),
                'location' => (string) ($row['location'] ?? ''),
            ];
        }

        return $out;
    }

    /**
     * @param  mixed  $items
     * @return array<int, array<string, mixed>>
     */
    protected function normalizeProjects($items): array
    {
        $out = [];
        foreach ($this->asList($items) as $row) {
            if (! is_array($row) || $this->isEmptyRow($row)) {
                continue;
            }

            $out[] = [
                'projectTitle' => (string) ($row['projectTitle'] ?? $row['title'] ?? $row['name'] ?? ''),
                'roles' => (string) ($row['roles'] ?? $row['role'] ?? ''),
                'duration' => (string) ($row['duration'] ?? ''),
                'projectDescription' => (string) ($row['projectDescription'] ?? $row['description'] ?? ''),
            ];
        }

        return $out;
    }

    /**
     * @param  mixed  $items
     * @return array<int, array<string, mixed>>
     */
    protected function normalizeReference($items): array
    {
        $out = [];
        foreach ($this->asList($items) as $row) {
            if (! is_array($row) || $this->isEmptyRow($row)) {
                continue;
            }

            $contact = (string) ($row['contact'] ?? '');
            $email = (string) ($row['email'] ?? '');
            $phone = (string) ($row['phone'] ?? '');
            if ($contact !== '') {
                if (str_contains($contact, '@') && $email === '') {
                    $email = $contact;
                } elseif ($phone === '') {
                    $phone = $contact;
                }
            }

            $out[] = [
                'name' => (string) ($row['name'] ?? ''),
                'jobtitle' => (string) ($row['jobtitle'] ?? $row['jobTitle'] ?? $row['designation'] ?? $row['position'] ?? ''),
                'companyname' => (string) ($row['companyname'] ?? $row['company'] ?? $row['companyName'] ?? $row['organization'] ?? ''),
                'email' => $email,
                'phone' => $phone,
            ];
        }

        return $out;
    }

    /**
     * @param  mixed  $items
     * @return array<int, array<string, mixed>>
     */
    protected function normalizeLanguage($items): array
    {
        $out = [];
        foreach ($this->asList($items) as $row) {
            if (is_string($row) && trim($row) !== '') {
                $out[] = ['name' => trim($row)];

                continue;
            }
            if (! is_array($row) || $this->isEmptyRow($row)) {
                continue;
            }

            $name = (string) ($row['name'] ?? $row['language'] ?? $row['lang'] ?? '');
            $level = (string) ($row['level'] ?? $row['proficiency'] ?? '');
            if ($name !== '' && $level !== '') {
                $name = $name.' ('.$level.')';
            }

            if ($name !== '') {
                $out[] = ['name' => $name];
            }
        }

        return $out;
    }

    /**
     * @param  mixed  $items
     * @return array<int, array<string, mixed>>
     */
    protected function normalizeAchivement($items): array
    {
        $out = [];
        foreach ($this->asList($items) as $row) {
            if (is_string($row) && trim($row) !== '') {
                $out[] = ['achivement' => trim($row)];

                continue;
            }
            if (! is_array($row) || $this->isEmptyRow($row)) {
                continue;
            }

            $text = (string) ($row['achivement'] ?? $row['achievement'] ?? '');
            if ($text === '') {
                $title = (string) ($row['title'] ?? '');
                $description = (string) ($row['description'] ?? '');
                $text = trim($title.(($title && $description) ? ' - ' : '').$description);
            }

            if ($text !== '') {
                $out[] = ['achivement' => $text];
            }
        }

        return $out;
    }

    /**
     * @param  mixed  $items
     * @return array<int, array<string, mixed>>
     */
    protected function normalizeInterests($items): array
    {
        $out = [];
        foreach ($this->asList($items) as $row) {
            if (is_string($row) && trim($row) !== '') {
                $out[] = ['hoby' => trim($row)];

                continue;
            }
            if (! is_array($row) || $this->isEmptyRow($row)) {
                continue;
            }

            $hoby = (string) ($row['hoby'] ?? $row['hobby'] ?? $row['interest'] ?? $row['name'] ?? $row['title'] ?? '');
            if ($hoby !== '') {
                $out[] = ['hoby' => $hoby];
            }
        }

        return $out;
    }

    /**
     * @param  mixed  $items
     * @return array<int, mixed>
     */
    protected function asList($items): array
    {
        $items = $this->asArray($items);
        if ($items === []) {
            return [];
        }

        return array_values($items);
    }

    /**
     * Decode JSON-string payloads from frontend into arrays.
     *
     * @param  mixed  $value
     * @return array<string|int, mixed>
     */
    protected function asArray($value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (is_string($value)) {
            $trimmed = trim($value);
            if ($trimmed === '') {
                return [];
            }
            $decoded = json_decode($trimmed, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return [];
    }

    /**
     * @param  array<string, mixed>  $row
     */
    protected function isEmptyRow(array $row): bool
    {
        foreach ($row as $value) {
            if ($value !== null && $value !== '') {
                return false;
            }
        }

        return true;
    }
}
