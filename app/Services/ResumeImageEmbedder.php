<?php

namespace App\Services;

/**
 * wkhtmltopdf cannot reliably load data:image URLs (and HTML-escaped = / +).
 * Decode img src, then write photos to temp files with file:// paths.
 */
class ResumeImageEmbedder
{
    public const VERSION = 'resume-image-embedder-v1';

    /**
     * @return array{0: string, 1: list<string>}
     */
    public static function embed(string $html): array
    {
        $files = [];
        $html = self::unescapeImgSources($html);

        $replaced = preg_replace_callback(
            '/(<img\b[^>]*\bsrc=["\'])([^"\']+)(["\'])/i',
            static function (array $m) use (&$files): string {
                $src = html_entity_decode($m[2], ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $path = self::dataUriToFile($src);
                if ($path === null) {
                    return $m[1].$src.$m[3];
                }
                $files[] = $path;

                return $m[1].'file://'.$path.$m[3];
            },
            $html
        );

        if (is_string($replaced)) {
            $html = $replaced;
        }

        return [$html, $files];
    }

    /**
     * @param  list<string>  $files
     */
    public static function cleanup(array $files): void
    {
        foreach ($files as $file) {
            if (is_string($file) && is_file($file) && str_contains($file, 'resume-img-')) {
                @unlink($file);
            }
        }
    }

    public static function unescapeImgSources(string $html): string
    {
        $replaced = preg_replace_callback(
            '/(<img\b[^>]*\bsrc=["\'])([^"\']+)(["\'])/i',
            static function (array $m): string {
                return $m[1].html_entity_decode($m[2], ENT_QUOTES | ENT_HTML5, 'UTF-8').$m[3];
            },
            $html
        );

        return is_string($replaced) ? $replaced : $html;
    }

    protected static function dataUriToFile(string $src): ?string
    {
        $src = trim($src);
        if (! preg_match('#^data:image/([a-zA-Z0-9+.-]+);base64,(.+)$#s', $src, $m)) {
            return null;
        }

        $mime = strtolower($m[1]);
        $ext = match (true) {
            str_contains($mime, 'svg') => 'svg',
            str_contains($mime, 'jpeg'), $mime === 'jpg' => 'jpg',
            $mime === 'gif' => 'gif',
            $mime === 'webp' => 'webp',
            default => 'png',
        };

        $binary = base64_decode(preg_replace('/\s+/', '', $m[2]) ?? '', true);
        if ($binary === false || $binary === '') {
            return null;
        }

        $path = sys_get_temp_dir().DIRECTORY_SEPARATOR.'resume-img-'.uniqid('', true).'.'.$ext;
        if (@file_put_contents($path, $binary) === false) {
            return null;
        }

        return $path;
    }
}
