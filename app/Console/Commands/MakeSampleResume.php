<?php

namespace App\Console\Commands;

use App\Http\Controllers\ResumeBuilderController;
use Illuminate\Console\Command;
use Illuminate\Http\Request;

/**
 * Run on Hostinger SSH (no browser / no Postman / no WAF):
 *
 *   cd ~/domains/resumemaker.com/public_html/server
 *   /opt/alt/php81/usr/bin/php artisan resume:make-sample
 *
 * Then open the printed PDF URL or download from File Manager:
 *   public_html/server/public/<timestamp>.pdf
 */
class MakeSampleResume extends Command
{
    protected $signature = 'resume:make-sample {--format=resume31.html}';

    protected $description = 'Generate a sample resume PDF with Skills/Education filled (SSH-safe)';

    public function handle(ResumeBuilderController $controller): int
    {
        $payload = [
            'format' => (string) $this->option('format'),
            'Personal Details' => [
                'Name' => 'John Doe',
                'Address' => '123 Main Street, New York, NY 10001',
                'Email' => 'johndoe@example.com',
                'Phone' => '+1 555-123-4567',
                'DOB' => '1995-08-15',
                'jobTitle' => 'Senior Full Stack Developer',
            ],
            'Objective' => [
                'Objective' => 'Results-driven Software Engineer with 6+ years of experience designing scalable web applications and RESTful APIs.',
            ],
            'Skills' => [
                ['name' => 'PHP / Laravel', 'expertise' => '90'],
                ['name' => 'Node.js', 'expertise' => '85'],
                ['name' => 'MySQL', 'expertise' => '80'],
                ['name' => 'Docker', 'expertise' => '70'],
            ],
            'Education' => [
                [
                    'degree' => 'Bachelor of Science in Computer Science',
                    'institution' => 'State University',
                    'startYear' => '2013',
                    'endYear' => '2017',
                    'grade' => '3.8 GPA',
                ],
            ],
            'Experience' => [
                [
                    'designation' => 'Lead Backend Developer',
                    'company' => 'Tech Solutions Inc.',
                    'location' => 'New York, NY',
                    'startDate' => '2020',
                    'endDate' => 'Present',
                    'description' => 'Architected microservices, managed database optimization, and handled high-throughput API endpoints.',
                ],
                [
                    'designation' => 'Junior Web Developer',
                    'company' => 'Digital Agency',
                    'location' => 'Boston, MA',
                    'startDate' => '2017',
                    'endDate' => '2020',
                    'description' => 'Built responsive user interfaces and integrated third-party REST services.',
                ],
            ],
            'Projects' => [
                [
                    'title' => 'Automated Resume Builder',
                    'role' => 'Lead Architect',
                    'description' => 'Engineered a headless PDF generator rendering complex templates using Puppeteer and Laravel.',
                ],
            ],
            'Reference' => [
                [
                    'name' => 'Jane Smith',
                    'designation' => 'Engineering Director',
                    'company' => 'Tech Solutions Inc.',
                    'contact' => 'janesmith@techsolutions.com',
                ],
            ],
            'Language' => [
                ['language' => 'English', 'level' => 'Native'],
                ['language' => 'Spanish', 'level' => 'Intermediate'],
            ],
            'Achivement' => [
                [
                    'title' => 'Employee of the Year 2023',
                    'description' => 'Awarded for exceptional contribution to cloud infrastructure migration.',
                ],
            ],
            'Interests' => [
                'Open Source Contributing',
                'Chess',
                'Technical Writing',
            ],
        ];

        $request = Request::create('/api/resume/create', 'POST', $payload);
        $response = $controller->createResume($request);
        $json = $response->getData(true);

        $this->info('resume_engine: '.($json['resume_engine'] ?? 'MISSING'));
        $this->info('mapper_version: '.($json['mapper_version'] ?? 'MISSING'));
        $this->info('debug_first_skill: '.($json['debug_first_skill'] ?? ''));
        $this->info('debug_education_course: '.($json['debug_education_course'] ?? ''));
        $this->info('pdf: '.($json['result'] ?? ''));
        $this->info('path: '.($json['pdf_full_path'] ?? ''));

        if (($json['resume_engine'] ?? '') !== 'laravel') {
            $this->error('Not Laravel API (old Node?). Response must include resume_engine=laravel');

            return 1;
        }

        if (($json['debug_first_skill'] ?? '') === '') {
            $this->error('Mapping failed — Skills empty after map.');

            return 1;
        }

        $this->info('OK — Skills/Education mapped. Download the PDF from File Manager.');

        return 0;
    }
}
