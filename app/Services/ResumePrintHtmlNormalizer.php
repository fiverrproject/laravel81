<?php

namespace App\Services;

/**
 * Minimal fixes only — layouts come from resources/resume-builder/*.html (see resumeformat.zip).
 */
class ResumePrintHtmlNormalizer
{
    public const VERSION = 'resume-print-normalizer-v4';

    public static function apply(string $html): string
    {
        $html = self::stripExternalFontImports($html);
        $html = self::expandCssVariables($html);
        $html = self::normalizeRgb($html);
        $html = self::normalizeGradientColors($html);
        $html = self::injectPrintColorAdjust($html);

        return $html;
    }

    /** wkhtmltopdf / Qt WebKit do not support CSS custom properties. */
    public static function expandCssVariables(string $html): string
    {
        $vars = [];
        if (preg_match_all('/--([a-zA-Z0-9_-]+)\s*:\s*([^;}{]+)/', $html, $m, PREG_SET_ORDER)) {
            foreach ($m as $row) {
                $vars[trim($row[1])] = trim($row[2]);
            }
        }
        if ($vars === []) {
            return $html;
        }

        return (string) preg_replace_callback(
            '/var\(\s*--([a-zA-Z0-9_-]+)\s*(?:,\s*([^)]+))?\)/',
            static function (array $match) use ($vars): string {
                $name = $match[1];
                if (isset($vars[$name]) && $vars[$name] !== '') {
                    return $vars[$name];
                }

                return isset($match[2]) ? trim($match[2]) : $match[0];
            },
            $html
        );
    }

    protected static function stripExternalFontImports(string $html): string
    {
        $html = (string) preg_replace('/@import\s+url\s*\([^)]+\)\s*;?/i', '', $html);
        $html = (string) preg_replace('/<link[^>]+fonts\.googleapis\.com[^>]*>/i', '', $html);

        return (string) preg_replace('/<link[^>]+rel=["\']?stylesheet["\']?[^>]*>/i', '', $html);
    }

    protected static function normalizeRgb(string $html): string
    {
        $html = (string) preg_replace(
            '/rgb\(\s*(\d+)\s+(\d+)\s+(\d+)\s*\)/',
            'rgb($1, $2, $3)',
            $html
        );

        return (string) preg_replace(
            '/rgba\(\s*(\d+)\s+(\d+)\s+(\d+)\s*\/\s*([^)]+)\)/',
            'rgba($1, $2, $3, $4)',
            $html
        );
    }

    protected static function normalizeGradientColors(string $html): string
    {
        return (string) preg_replace_callback(
            '/linear-gradient\(([^)]*)\)/i',
            static function (array $m): string {
                $inner = (string) preg_replace(
                    '/rgb\(\s*(\d+)\s+(\d+)\s+(\d+)\s*\)/',
                    'rgb($1, $2, $3)',
                    $m[1]
                );

                return 'linear-gradient('.$inner.')';
            },
            $html
        );
    }

    /** Backgrounds/gradients from resumeformat templates must print like the JPG previews. */
    protected static function injectPrintColorAdjust(string $html): string
    {
        $styles = <<<'CSS'
<style id="resume-print-normalizer-v4">
  @page { size: A4 portrait; margin: 0; }
  html, body {
    margin: 0;
    padding: 0;
    -webkit-print-color-adjust: exact !important;
    print-color-adjust: exact !important;
  }
</style>
CSS;

        if (stripos($html, '</head>') !== false) {
            return preg_replace('/<\/head>/i', $styles.'</head>', $html, 1) ?? ($styles.$html);
        }

        return $styles.$html;
    }
}
