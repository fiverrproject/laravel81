<?php

namespace App\Services;

use Illuminate\Http\Request;

/**
 * Pure Laravel resume PDF pipeline (replaces old Node createResume).
 */
class ResumePdfService
{
    public function __construct(
        protected HandlebarsService $handlebars,
        protected BrowserRenderService $browser
    ) {}

    /**
     * @return array{payload: array<string, mixed>, data: array<string, mixed>}
     */
    public function prepare(Request $request): array
    {
        $payload = $this->parsePayload($request);
        $data = ResumeTemplateDataMapper::fromApiPayload($payload);

        $mappedSkills = is_array($data['Skills'] ?? null) ? count($data['Skills']) : 0;
        $payloadSkills = is_array($payload['Skills'] ?? null) ? count($payload['Skills']) : 0;
        if ($mappedSkills === 0 && $payloadSkills > 0) {
            $data = ResumeTemplateDataMapper::fromApiPayload($payload);
        }

        return ['payload' => $payload, 'data' => $data];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $data
     */
    public function renderHtml(array $payload, array $data): string
    {
        $format = (string) ($payload['format'] ?? 'resume31.html');
        $templatePath = $this->handlebars->resolveTemplatePath($format);
        $html = $this->handlebars->render($templatePath, $data);

        if ($this->isResume12($payload)) {
            $html = Resume12PostRenderFix::apply($html, $data, $payload);
        }

        $html = ResumeWkhtmlCssFix::apply($html);

        if ($this->isResume30($payload)) {
            $html = Resume30PrintFix::apply($html);
        }

        if ($this->isLatest($payload)) {
            $html = ResumeLatestPrintFix::apply($html);
        }

        return $html;
    }

    /**
     * @return array{filename: string, path: string}
     */
    public function createPdfFile(string $html, ?string $directory = null): array
    {
        $filename = (string) round(microtime(true) * 1000).'.pdf';
        if ($directory !== null) {
            $directory = rtrim($directory, DIRECTORY_SEPARATOR);
            if (! is_dir($directory)) {
                mkdir($directory, 0755, true);
            }
            $path = $directory.DIRECTORY_SEPARATOR.$filename;
        } else {
            $path = public_path($filename);
        }

        $this->browser->savePdf($html, $path);

        return ['filename' => $filename, 'path' => $path];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function apiMeta(array $data): array
    {
        $firstSkill = '';
        if (is_array($data['Skills'] ?? null) && isset($data['Skills'][0]['skillName'])) {
            $firstSkill = (string) $data['Skills'][0]['skillName'];
        }

        return [
            'resume_engine' => 'laravel',
            'pdf_engine' => $this->browser->pdfEngine(),
            'print_normalizer_version' => ResumePrintHtmlNormalizer::VERSION,
            'mapper_version' => ResumeTemplateDataMapper::VERSION,
            'section_html_version' => ResumeSectionHtmlBuilder::VERSION,
            'post_render_version' => Resume12PostRenderFix::VERSION,
            'resume30_print_fix' => Resume30PrintFix::VERSION,
            'latest_print_fix' => ResumeLatestPrintFix::VERSION,
            'image_embedder_version' => ResumeImageEmbedder::VERSION,
            'wkhtml_css_fix' => ResumeWkhtmlCssFix::VERSION,
            'debug_first_skill' => $firstSkill,
            'debug_education_course' => is_array($data['Education'] ?? null)
                ? (string) ($data['Education'][0]['course'] ?? '')
                : '',
            'template_count' => ResumeTemplatesInstaller::resumeTemplateCount(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function parsePayload(Request $request): array
    {
        $payload = $request->all();
        $rawBody = $request->getContent();
        $decoded = is_string($rawBody) && $rawBody !== '' ? json_decode($rawBody, true) : null;

        if (is_array($decoded)) {
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
     * @param  array<string, mixed>  $payload
     */
    protected function isResume12(array $payload): bool
    {
        return stripos((string) ($payload['format'] ?? ''), 'resume12.html') !== false;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function isResume30(array $payload): bool
    {
        return stripos((string) ($payload['format'] ?? ''), 'resume30.html') !== false;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function isLatest(array $payload): bool
    {
        return stripos((string) ($payload['format'] ?? ''), 'latest.html') !== false;
    }
}
