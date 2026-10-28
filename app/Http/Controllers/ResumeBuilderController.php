<?php

namespace App\Http\Controllers;

use App\Services\BrowserRenderService;
use App\Services\HandlebarsService;
use App\Services\Resume12PostRenderFix;
use App\Services\ResumePdfService;
use App\Services\ResumeSectionHtmlBuilder;
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
        protected ResumePdfService $resumePdf
    ) {}

    public function createResume(Request $request)
    {
        ['payload' => $payload, 'data' => $data] = $this->resumePdf->prepare($request);

        $this->writeDeploySnapshot($payload, $data);

        if ($request->boolean('debug_normalize') || ! empty($payload['debug_normalize'])) {
            $sections = ResumeSectionHtmlBuilder::injectResume12Sections($data);
            $templatePath = resource_path('resume-builder/'.($payload['format'] ?? 'resume31.html'));

            return response()->json($this->resumePdf->apiMeta($data) + [
                'status' => true,
                'mapped' => $data,
                'template_resume12_marker' => is_file($templatePath)
                    ? str_contains((string) file_get_contents($templatePath), 'resume12-php-sections-v1')
                    : false,
                'section_html_lengths' => array_map(
                    static fn ($html) => is_string($html) ? strlen($html) : 0,
                    $sections
                ),
            ]);
        }

        $html = $this->resumePdf->renderHtml($payload, $data);
        @file_put_contents(public_path('LAST_RENDER.html'), $html);

        $pdf = $this->resumePdf->createPdfFile($html);

        return response()->json($this->resumePdf->apiMeta($data) + [
            'status' => true,
            'result' => $pdf['filename'],
            'pdf_full_path' => $pdf['path'],
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $data
     */
    protected function writeDeploySnapshot(array $payload, array $data): void
    {
        @file_put_contents(public_path('LAST_MAP.json'), json_encode([
            'time' => date('c'),
            'resume_engine' => 'laravel',
            'mapper_version' => ResumeTemplateDataMapper::VERSION,
            'format' => $payload['format'] ?? null,
            'Skills' => $data['Skills'] ?? null,
            'Education' => $data['Education'] ?? null,
            'Language' => $data['Language'] ?? null,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    public function deployFingerprint()
    {
        $template = resource_path('resume-builder/resume12.html');
        $body = is_file($template) ? (string) file_get_contents($template) : '';

        return response()->json([
            'status' => true,
            'resume_engine' => 'laravel',
            'mapper_version' => ResumeTemplateDataMapper::VERSION,
            'section_html_version' => ResumeSectionHtmlBuilder::VERSION,
            'post_render_version' => Resume12PostRenderFix::VERSION,
            'resume12_has_php_marker' => str_contains($body, 'resume12-php-sections-v1'),
            'base_path' => base_path(),
            'public_path' => public_path(),
        ]);
    }

    public function mapperCheck()
    {
        $mapped = ResumeTemplateDataMapper::fromApiPayload([
            'format' => 'resume12.html',
            'Personal Details' => ['Name' => 'T', 'jobTitle' => 'Dev'],
            'Skills' => [['name' => 'PHP', 'expertise' => '90']],
            'Education' => [['degree' => 'BSc', 'institution' => 'Uni', 'startYear' => '2013', 'endYear' => '2017']],
        ]);

        return response()->json([
            'status' => true,
            'resume_engine' => 'laravel',
            'mapper_version' => ResumeTemplateDataMapper::VERSION,
            'debug_first_skill' => $mapped['Skills'][0]['skillName'] ?? null,
            'sample_mapped' => $mapped,
        ]);
    }

    public function createCV(Request $request)
    {
        ['payload' => $payload, 'data' => $data] = $this->resumePdf->prepare($request);
        $html = $this->resumePdf->renderHtml($payload, $data);
        $pdf = $this->resumePdf->createPdfFile($html);
        $base64 = base64_encode((string) file_get_contents($pdf['path']));
        @unlink($pdf['path']);

        return response()->json($this->resumePdf->apiMeta($data) + [
            'status' => true,
            'result' => $base64,
        ]);
    }

    public function createResumeForWeb(Request $request)
    {
        ['payload' => $payload, 'data' => $data] = $this->resumePdf->prepare($request);
        $html = $this->resumePdf->renderHtml($payload, $data);
        $pdf = $this->resumePdf->createPdfFile($html, public_path('resume-web'));

        return response()->json($this->resumePdf->apiMeta($data) + [
            'status' => true,
            'result' => $pdf['filename'],
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
