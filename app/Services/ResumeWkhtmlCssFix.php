<?php

namespace App\Services;

/**
 * Qt WebKit (wkhtmltopdf) does not support modern flex/grid.
 * Rewrite CSS to -webkit-box / table so every resume*.html matches its JPG.
 */
class ResumeWkhtmlCssFix
{
    public const VERSION = 'resume-wkhtml-css-fix-v2';

    public static function apply(string $html): string
    {
        $html = self::expandCssVariables($html);

        if (stripos($html, 'id="resume-wkhtml-css-fix"') === false) {
            $html = self::injectFallbacks($html);
        }

        $html = self::rewriteStyleTags($html);
        $html = self::rewriteInlineStyles($html);

        return $html;
    }

    /** wkhtmltopdf does not support CSS custom properties. */
    protected static function expandCssVariables(string $html): string
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

    protected static function rewriteStyleTags(string $html): string
    {
        $replaced = preg_replace_callback(
            '/(<style\b[^>]*>)(.*?)(<\/style>)/is',
            static function (array $m): string {
                return $m[1].self::rewriteCss($m[2]).$m[3];
            },
            $html
        );

        return is_string($replaced) ? $replaced : $html;
    }

    protected static function rewriteInlineStyles(string $html): string
    {
        $replaced = preg_replace_callback(
            '/\bstyle=(["\'])(.*?)\1/is',
            static function (array $m): string {
                return 'style='.$m[1].self::rewriteCss($m[2]).$m[1];
            },
            $html
        );

        return is_string($replaced) ? $replaced : $html;
    }

    protected static function rewriteCss(string $css): string
    {
        $css = (string) preg_replace(
            '/display\s*:\s*flex\s*;/i',
            'display:-webkit-box;display:-webkit-flex;display:flex;',
            $css
        );
        $css = (string) preg_replace(
            '/flex-direction\s*:\s*row(?:-reverse)?\s*;/i',
            '-webkit-box-orient:horizontal;-webkit-box-direction:normal;flex-direction:row;',
            $css
        );
        $css = (string) preg_replace(
            '/flex-direction\s*:\s*column(?:-reverse)?\s*;/i',
            '-webkit-box-orient:vertical;-webkit-box-direction:normal;flex-direction:column;',
            $css
        );
        $css = (string) preg_replace(
            '/justify-content\s*:\s*space-between\s*;/i',
            '-webkit-box-pack:justify;justify-content:space-between;',
            $css
        );
        $css = (string) preg_replace(
            '/justify-content\s*:\s*center\s*;/i',
            '-webkit-box-pack:center;justify-content:center;',
            $css
        );
        $css = (string) preg_replace(
            '/justify-content\s*:\s*(?:flex-)?end\s*;/i',
            '-webkit-box-pack:end;justify-content:flex-end;',
            $css
        );
        $css = (string) preg_replace(
            '/align-items\s*:\s*center\s*;/i',
            '-webkit-box-align:center;align-items:center;',
            $css
        );
        $css = (string) preg_replace(
            '/align-items\s*:\s*stretch\s*;/i',
            '-webkit-box-align:stretch;align-items:stretch;',
            $css
        );
        $css = (string) preg_replace(
            '/flex\s*:\s*1(?:\s+1(?:\s+0%)?)?\s*;/i',
            '-webkit-box-flex:1;flex:1;',
            $css
        );
        $css = (string) preg_replace(
            '/display\s*:\s*grid\s*;/i',
            'display:table;width:100%;table-layout:fixed;',
            $css
        );

        return $css;
    }

    protected static function injectFallbacks(string $html): string
    {
        $styles = <<<'CSS'
<style id="resume-wkhtml-css-fix">
  .content.d-flex,
  .container.d-flex,
  .main.d-flex,
  .main-content.d-flex,
  .header.d-flex,
  .two-column,
  .resume {
    display: table !important;
    width: 100% !important;
    table-layout: fixed;
  }
  .content.d-flex > .left-part,
  .content.d-flex > .right-part,
  .header.d-flex > .left-part,
  .header.d-flex > .right-part,
  .header > .left-part,
  .header > .right-part,
  .two-column > .column1,
  .two-column > .column2,
  .resume > .resume_left,
  .resume > .resume_right,
  .main.d-flex > div,
  .main-content.d-flex > div,
  .container.d-flex > .left-part,
  .container.d-flex > .right-part,
  .container.d-flex > .left,
  .container.d-flex > .right {
    display: table-cell !important;
    vertical-align: top;
  }
  .header > .left-part,
  .content > .left-part,
  .content.d-flex > .left-part {
    width: 35%;
  }
  .header > .right-part,
  .content > .right-part,
  .content.d-flex > .right-part {
    width: 65% !important;
  }
  .resume > .resume_left {
    width: 280px;
  }
  .resume > .resume_right {
    width: auto;
  }
  .grid-container1,
  .grid-container {
    display: table !important;
    width: 100%;
    table-layout: fixed;
  }
  .grid-item1,
  .grid-item {
    display: table-cell !important;
    vertical-align: top;
  }
  .progress {
    display: block;
    overflow: hidden;
  }
  .progress-bar {
    display: block;
    float: left;
    height: 100%;
  }
  img {
    max-width: 100%;
  }
</style>
CSS;

        if (stripos($html, '</head>') !== false) {
            return preg_replace('/<\/head>/i', $styles.'</head>', $html, 1) ?? ($styles.$html);
        }

        return $styles.$html;
    }
}
