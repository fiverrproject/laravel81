<?php

namespace App\Console\Commands;

use App\Http\Controllers\ResumeBuilderController;
use App\Services\HandlebarsService;
use App\Services\ResumeSectionHtmlBuilder;
use App\Services\ResumeTemplateDataMapper;
use App\Services\ResumeTemplatesInstaller;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;

class VerifyResumeDeploy extends Command
{
    protected $signature = 'resume:verify-deploy';

    protected $description = 'Print PASS/FAIL for every file and setting required on Hostinger (run over SSH)';

    public function handle(): int
    {
        $checks = [];

        $checks['class_ResumeSectionHtmlBuilder'] = class_exists(ResumeSectionHtmlBuilder::class);
        $checks['class_ResumeTemplateDataMapper'] = class_exists(ResumeTemplateDataMapper::class);
        $checks['mapper_version'] = ResumeTemplateDataMapper::VERSION;
        $checks['section_html_version'] = ResumeSectionHtmlBuilder::VERSION;
        $checks['template_count'] = ResumeTemplatesInstaller::resumeTemplateCount();
        $checks['templates_ok'] = $checks['template_count'] >= 35;

        $controller = app_path('Http/Controllers/ResumeBuilderController.php');
        $checks['controller_exists'] = is_file($controller);
        $controllerBody = $checks['controller_exists'] ? file_get_contents($controller) : '';
        $checks['controller_uses_mapper'] = str_contains($controllerBody, 'ResumeTemplateDataMapper::fromApiPayload');
        $checks['controller_has_resume_engine'] = str_contains($controllerBody, "'resume_engine'");

        $handlebars = app_path('Services/HandlebarsService.php');
        $checks['handlebars_calls_mapper'] = is_file($handlebars)
            && str_contains(file_get_contents($handlebars), 'embedResume12PhpSections');

        $mapperFile = app_path('Services/ResumeTemplateDataMapper.php');
        $checks['mapper_file_on_disk'] = is_file($mapperFile);

        $sample = ResumeTemplateDataMapper::fromApiPayload([
            'format' => 'resume25.html',
            'Personal Details' => ['Name' => 'T', 'jobTitle' => 'Dev'],
            'Skills' => [['name' => 'PHP', 'expertise' => '90']],
            'Education' => [['degree' => 'BSc', 'institution' => 'Uni', 'startYear' => '2013', 'endYear' => '2017']],
        ]);
        $checks['sample_skillName'] = is_array($sample['Skills'] ?? null)
            ? ($sample['Skills'][0]['skillName'] ?? '')
            : '';
        $checks['mapping_works'] = $checks['sample_skillName'] === 'PHP';

        $checks['route_api_resume_create'] = collect(Route::getRoutes())->contains(
            fn ($r) => in_array('POST', $r->methods(), true)
                && str_contains($r->uri(), 'resume/create')
        );

        $fail = 0;
        foreach ($checks as $key => $value) {
            $ok = $value === true || ($key === 'mapper_version' && is_string($value) && $value !== '')
                || ($key === 'section_html_version' && is_string($value) && $value !== '')
                || ($key === 'sample_skillName' && $value === 'PHP')
                || ($key === 'template_count' && is_int($value));
            if ($key === 'template_count' || $key === 'mapper_version' || $key === 'section_html_version' || $key === 'sample_skillName') {
                $this->line("  {$key}: {$value}");
                if ($key === 'template_count' && $value < 35) {
                    $fail++;
                    $this->error('  FAIL templates — run: php artisan resume:sync-templates');
                }
                continue;
            }
            if ($ok) {
                $this->info("  PASS {$key}");
            } else {
                $this->error("  FAIL {$key}");
                $fail++;
            }
        }

        $this->newLine();
        $this->line('base_path: '.base_path());
        $this->line('public_path: '.public_path());

        if ($fail > 0) {
            $this->error("{$fail} check(s) failed — upload missing files from laravel81/ on your PC.");

            return 1;
        }

        $this->info('All checks passed. If Postman still shows blank PDF, response is NOT from this Laravel app (Node or wrong domain).');
        $this->line('Postman must return: resume_engine=laravel, mapper_version='.ResumeTemplateDataMapper::VERSION);

        return 0;
    }
}
