<?php

namespace App\Http\Controllers;

use App\Services\BrowserRenderService;
use App\Services\HandlebarsService;
use App\Services\ResumeDataNormalizer;
use App\Services\ResumeTemplateDataMapper;
use App\Services\ResumeTemplatesInstaller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Throwable;

class ResumeBuilderController extends Controller
{
    public function __construct(
        protected HandlebarsService $handlebars,
        protected BrowserRenderService $browser,
        protected ResumeDataNormalizer $normalizer
    ) {}

    public function createResume(Request $request)
    {
        $payload = $this->parseResumePayload($request);
        $data = ResumeTemplateDataMapper::fromApiPayload($payload);

        @file_put_contents(public_path('LAST_MAP.json'), json_encode([
            'time' => date('c'),
            'resume_engine' => 'laravel',
            'mapper_version' => ResumeTemplateDataMapper::VERSION,
            'format' => $payload['format'] ?? null,
            'template_count' => ResumeTemplatesInstaller::resumeTemplateCount(),
            'Skills' => $data['Skills'] ?? null,
            'Education' => $data['Education'] ?? null,
            'Language' => $data['Language'] ?? null,
            'Interests' => $data['Interests'] ?? null,
            'Projects' => $data['Projects'] ?? null,
            'Achivement' => $data['Achivement'] ?? null,
            'template_path' => resource_path('resume-builder/'.($payload['format'] ?? 'resume31.html')),
            'template_exists' => is_file(resource_path('resume-builder/'.($payload['format'] ?? 'resume31.html'))),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        if ($request->boolean('debug_normalize') || ! empty($payload['debug_normalize'])) {
            return response()->json($this->resumeApiMeta($data) + [
                'status' => true,
                'mapped' => $data,
            ]);
        }

        $pdffile = (string) round(microtime(true) * 1000).'.pdf';
        $outputPath = public_path($pdffile);

        $templatePath = $this->handlebars->resolveTemplatePath((string) ($payload['format'] ?? 'resume31.html'));
        $html = $this->handlebars->render($templatePath, $data);

        @file_put_contents(public_path('LAST_RENDER.html'), $html);
        @file_put_contents(public_path('LAST_RENDER.meta.json'), json_encode([
            'time' => date('c'),
            'resume_engine' => 'laravel',
            'mapper_version' => ResumeTemplateDataMapper::VERSION,
            'format' => $payload['format'] ?? null,
            'html_contains_php_skill' => str_contains($html, 'PHP / Laravel'),
            'html_contains_education' => str_contains($html, 'State University') || str_contains($html, 'Computer Science'),
            'html_byte_length' => strlen($html),
        ], JSON_PRETTY_PRINT));

        $this->browser->savePdf($html, $outputPath);

        return response()->json($this->resumeApiMeta($data) + [
            'status' => true,
            'result' => $pdffile,
            'pdf_full_path' => $outputPath,
            'html_preview' => url('LAST_RENDER.html'),
            'html_contains_skill_sample' => is_array($data['Skills'] ?? null)
                ? str_contains($html, (string) ($data['Skills'][0]['skillName'] ?? ''))
                : false,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function parseResumePayload(Request $request): array
    {
        $payload = $request->all();
        $rawBody = $request->getContent();
        $decoded = is_string($rawBody) && $rawBody !== '' ? json_decode($rawBody, true) : null;

        if (is_array($decoded)) {
            // Some hosts populate only part of JSON via $request->all(); body is source of truth.
            if ($payload === [] || ! isset($payload['Skills']) && isset($decoded['Skills'])) {
                $payload = $decoded;
            } else {
                $payload = array_replace_recursive($decoded, $payload);
            }
        }

        foreach ([
            'Skills', 'Education', 'Experience', 'Language', 'Interests',
            'Projects', 'Reference', 'Achivement', 'Achievement',
        ] as $sectionKey) {
            if (! isset($payload[$sectionKey]) || ! is_string($payload[$sectionKey])) {
                continue;
            }
            $section = json_decode(trim($payload[$sectionKey]), true);
            if (is_array($section)) {
                $payload[$sectionKey] = $section;
            }
        }

        return $payload;
    }

    /**
     * If Postman response is missing these fields, you are NOT hitting Laravel (old Node still running).
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function resumeApiMeta(array $data): array
    {
        $firstSkill = '';
        if (is_array($data['Skills'] ?? null) && isset($data['Skills'][0]['skillName'])) {
            $firstSkill = (string) $data['Skills'][0]['skillName'];
        }

        $meta = [
            'resume_engine' => 'laravel',
            'mapper_version' => ResumeTemplateDataMapper::VERSION,
            'debug_first_skill' => $firstSkill,
            'debug_education_course' => is_array($data['Education'] ?? null)
                ? (string) ($data['Education'][0]['course'] ?? '')
                : '',
            'template_count' => ResumeTemplatesInstaller::resumeTemplateCount(),
        ];

        if ($firstSkill === '' && ! empty($data['Skills'] ?? null)) {
            $meta['mapping_warning'] = 'Skills present but skillName empty — run php artisan resume:verify-deploy on server';
        }

        return $meta;
    }

    /**
     * Live deploy check (Laravel route — not a standalone .php file).
     * GET /api/resume/mapper-check
     * GET /resume/mapper-check
     */
    public function mapperCheck()
    {
        $mapped = ResumeTemplateDataMapper::map([
            'Skills' => [['name' => 'PHP / Laravel', 'expertise' => '90']],
            'Education' => [['degree' => 'BSc CS', 'institution' => 'State Uni', 'startYear' => '2013', 'endYear' => '2017', 'grade' => '3.8']],
            'Experience' => [['designation' => 'Lead', 'company' => 'Tech', 'startDate' => '2020', 'endDate' => 'Present', 'description' => 'APIs']],
            'Language' => [['language' => 'English', 'level' => 'Native']],
            'Interests' => ['Chess', 'Writing'],
            'Projects' => [['title' => 'Resume Builder', 'role' => 'Lead', 'description' => 'PDF']],
            'Achivement' => [['title' => 'Award', 'description' => 'Cloud']],
        ]);

        return response()->json([
            'status' => true,
            'resume_engine' => 'laravel',
            'mapper_version' => ResumeTemplateDataMapper::VERSION,
            'controller_file' => __FILE__,
            'controller_mtime' => @date('c', filemtime(__FILE__)),
            'base_path' => base_path(),
            'public_path' => public_path(),
            'template_count' => ResumeTemplatesInstaller::resumeTemplateCount(),
            'template_exists' => is_file(resource_path('resume-builder/resume31.html')),
            'debug_first_skill' => $mapped['Skills'][0]['skillName'] ?? null,
            'debug_education_course' => $mapped['Education'][0]['course'] ?? null,
            'sample_mapped' => $mapped,
            'node_still_running_hint' => 'If POST /api/resume/create returns only status+result, stop Node/PM2 and route to Laravel public/index.php',
        ]);
    }

    public function createCV(Request $request)
    {
        $pdffile = (string) round(microtime(true) * 1000).'.pdf';
        $outputPath = public_path($pdffile);

        $data = (new ResumeDataNormalizer)->normalize($request->all(), 'Personal Details');
        $templatePath = $this->handlebars->resolveTemplatePath((string) $request->input('format'));
        $html = $this->handlebars->render($templatePath, $data);

        $this->browser->savePdf($html, $outputPath);

        $base64 = base64_encode((string) file_get_contents($outputPath));
        @unlink($outputPath);

        return response()->json([
            'status' => true,
            'result' => $base64,
        ]);
    }

    public function createResumeForWeb(Request $request)
    {
        $pdffile = (string) round(microtime(true) * 1000).'.pdf';
        $outputPath = public_path('resume-web/'.$pdffile);

        $data = (new ResumeDataNormalizer)->normalize($request->all(), 'personal_details');
        $templatePath = $this->handlebars->resolveTemplatePath((string) $request->input('format'));
        $html = $this->handlebars->render($templatePath, $data);

        $this->browser->savePdf($html, $outputPath);

        return response()->json([
            'status' => true,
            'result' => $pdffile,
        ]);
    }

    public function festivalPost(Request $request)
    {
        $imgfile = (string) round(microtime(true) * 1000).'.jpg';
        $outputPath = public_path('festival/'.$imgfile);

        $companyLogo = $request->input('companyLogo', '');
        $emptyLogo = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAoAAAAKAQMAAAC3/F3+AAAAAXNSR0IB2cksfwAAAAlwSFlzAAALEwAACxMBAJqcGAAAAANQTFRFAAAAp3o92gAAAAF0Uk5TAEDm2GYAAAALSURBVHicY2DABwAAHgAB9v3plgAAAABJRU5ErkJggg==';
        if ($companyLogo === $emptyLogo) {
            $companyLogo = '';
        }

        $data = [
            'address' => $request->input('address', ''),
            'website' => $request->input('website', ''),
            'phone' => $request->input('phone', ''),
            'email' => $request->input('email', ''),
            'name' => $request->input('name', ''),
            'companyLogo' => $companyLogo ?: '',
        ];

        $templatePath = $this->handlebars->resolveTemplatePath('festivalpost-builder/'.$request->input('format'));
        $html = $this->handlebars->render($templatePath, $data);

        $this->browser->saveScreenshot($html, $outputPath, 1080, 1920);

        return response()->json([
            'status' => true,
            'result' => $imgfile,
        ]);
    }

    public function statusMaker(Request $request)
    {
        $imgfile = (string) round(microtime(true) * 1000).'.jpg';
        $outputPath = public_path('statusmaker/'.$imgfile);

        $data = [
            'boy_name' => $request->input('boy_name', ''),
            'girls_name' => $request->input('girls_name', ''),
            'title' => $request->input('title', ''),
            'image' => $request->input('image', ''),
            'date' => $request->input('date', ''),
            'day_count' => $request->input('day_count', ''),
            'description' => $request->input('description', ''),
            'gender' => $request->input('gender', ''),
            'weight' => $request->input('weight', ''),
            'time' => $request->input('time', ''),
            'year_or_month' => $request->input('year_or_month', ''),
            'father_name' => $request->input('father_name', ''),
            'mother_name' => $request->input('mother_name', ''),
            'baby_img' => $request->input('baby_img', ''),
            'parent_img' => $request->input('parent_img', ''),
        ];

        $templatePath = $this->handlebars->resolveTemplatePath('statusmaker-builder/'.$request->input('format'));
        $html = $this->handlebars->render($templatePath, $data);

        $this->browser->saveScreenshot($html, $outputPath, 1000, 1000);

        return response()->json([
            'status' => true,
            'result' => $imgfile,
        ]);
    }

    public function invitationCard(Request $request)
    {
        $isPdf = $request->input('format_type') === 'pdf';
        $imgfile = $isPdf
            ? (string) round(microtime(true) * 1000).'.pdf'
            : (string) round(microtime(true) * 1000).'.jpg';

        $outputPath = $isPdf
            ? public_path('invitation_pdf/'.$imgfile)
            : public_path('invitation_img/'.$imgfile);

        $data = [
            'surname' => $request->input('surname', ''),
            'guest_name' => $request->input('guest_name', ''),
            'boy_name' => $request->input('boy_name', ''),
            'girls_name' => $request->input('girls_name', ''),
            'boy_father' => $request->input('boy_father', ''),
            'boy_mother' => $request->input('boy_mother', ''),
            'girl_father' => $request->input('girl_father', ''),
            'girl_mother' => $request->input('girl_mother', ''),
            'contact_no' => $request->input('contact_no', ''),
            'alt_contact_no' => $request->input('alt_contact_no', ''),
            'date' => $request->input('date', ''),
            'time' => $request->input('time', ''),
            'address1' => $request->input('address1', ''),
            'address2' => $request->input('address2', ''),
            'address3' => $request->input('address3', ''),
            'day_name' => $request->input('day_name', ''),
            'gujarati_year' => $request->input('gujarati_year', ''),
            'village_name' => $request->input('village_name', ''),
            'well_wisher' => $request->input('well_wisher', ''),
            'invitee' => $request->input('invitee', ''),
            'goddess' => $request->input('goddess', ''),
            'name_dead_person' => $request->input('name_dead_person', ''),
            'father_name' => $request->input('father_name', ''),
            'mother_name' => $request->input('mother_name', ''),
            'dead_date' => $request->input('dead_date', ''),
            'dead_day_name' => $request->input('dead_day_name', ''),
            'dead_gujarati_year' => $request->input('dead_gujarati_year', ''),
            'relative_name' => $request->input('relative_name', ''),
            'image' => $request->input('image', ''),
        ];

        $templatePath = $this->handlebars->resolveTemplatePath('invitation-builder/'.$request->input('format'));
        $html = $this->handlebars->render($templatePath, $data);

        if ($isPdf) {
            $this->browser->savePdf($html, $outputPath);
        } else {
            $this->browser->saveScreenshot($html, $outputPath, 1240, 1500);
        }

        return response()->json([
            'status' => true,
            'result' => $imgfile,
        ]);
    }

    public function instagram(Request $request)
    {
        try {
            $response = Http::asMultipart()->post('https://v3.saveig.app/api/ajaxSearch', [
                ['name' => 'q', 'contents' => (string) $request->input('url')],
                ['name' => 't', 'contents' => 'media'],
                ['name' => 'lang', 'contents' => 'en'],
            ]);

            $result = $response->json();

            if (! is_array($result)) {
                return response()->json([
                    'status' => false,
                    'error' => 'Invalid response from Instagram proxy.',
                ]);
            }

            if (! empty($result['mess']) && is_string($result['mess']) && str_contains($result['mess'], 'Video is private')) {
                return response()->json([
                    'status' => false,
                    'isPrivate' => true,
                ]);
            }

            $data = $result['data'] ?? '';
            if (! is_string($data) || $data === '') {
                return response()->json([
                    'status' => false,
                    'error' => 'No media data found.',
                ]);
            }

            preg_match('/href="([^"]*)/', $data, $hrefMatch);
            preg_match('/src="([^"]*)/', $data, $srcMatch);

            $downloadURL = $hrefMatch[1] ?? '';
            $thumb = $srcMatch[1] ?? '';
            $isImage = str_contains($data, 'Download Image');

            return response()->json([
                'status' => true,
                'html' => $data,
                'data' => [
                    'downloadURL' => $isImage ? $thumb : $downloadURL,
                    'thumb' => $thumb,
                    'media_formate' => $isImage ? '.jpg' : '.mp4',
                ],
            ]);
        } catch (Throwable $error) {
            return response()->json([
                'status' => false,
                'error' => $error->getMessage(),
            ]);
        }
    }

    public function versionCheck(Request $request)
    {
        $token = (string) $request->input('token', '');
        $configs = config('versioncheck', []);
        $result = $configs[$token] ?? new \stdClass;

        return response()->json([
            'status' => true,
            'result' => $result,
        ]);
    }

    public function testApi()
    {
        return response()->json([
            'success' => true,
            'resume_engine' => 'laravel',
            'mapper_version' => ResumeTemplateDataMapper::VERSION,
            'message' => 'Laravel resume API is working',
        ]);
    }

}
