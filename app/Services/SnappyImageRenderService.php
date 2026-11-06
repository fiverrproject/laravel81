<?php

namespace App\Services;

use Knp\Snappy\Image;
use RuntimeException;

/**
 * JPEG from HTML when Chrome is not installed (Hostinger uses wkhtmltoimage).
 */
class SnappyImageRenderService
{
    public function saveJpeg(string $html, string $outputPath, int $width, int $height): void
    {
        $binary = WkhtmltoimagePathResolver::tryResolve();
        if ($binary === null) {
            throw new RuntimeException(
                'JPEG screenshots need wkhtmltoimage (or Chrome). Run composer install so vendor/h4cc/wkhtmltoimage-amd64 is present.'
            );
        }

        $dir = dirname($outputPath);
        if ($dir !== '' && ! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $html = $this->prepareHtml($html, $width, $height);

        $snappy = new Image($binary);
        $snappy->setTimeout(180);
        $snappy->setOptions([
            'format' => 'jpg',
            'quality' => 90,
            'width' => $width,
            'height' => $height,
            'disable-smart-width' => true,
            'encoding' => 'UTF-8',
            'enable-local-file-access' => true,
            'javascript-delay' => 800,
            'load-error-handling' => 'ignore',
            'load-media-error-handling' => 'ignore',
        ]);

        try {
            $snappy->generateFromHtml($html, $outputPath, [], true);
        } catch (RuntimeException $e) {
            if (! is_file($outputPath) || filesize($outputPath) === 0) {
                throw $e;
            }
        }

        if (! is_file($outputPath) || filesize($outputPath) === 0) {
            throw new RuntimeException('wkhtmltoimage did not write: '.$outputPath);
        }
    }

    /**
     * wkhtmltoimage 0.12 uses old WebKit: no 100vh and no modern flexbox.
     * Festival templates are authored for Chrome, so rewrite those two before capture.
     */
    protected function prepareHtml(string $html, int $width, int $height): string
    {
        [$html, $images] = $this->parkDataUris($html);

        $html = preg_replace_callback('/(\d*\.?\d+)vh\b/', function (array $match) use ($height): string {
            return round(((float) $match[1] / 100) * $height, 2).'px';
        }, $html) ?? $html;

        $html = preg_replace_callback('/(\d*\.?\d+)vw\b/', function (array $match) use ($width): string {
            return round(((float) $match[1] / 100) * $width, 2).'px';
        }, $html) ?? $html;

        $html = str_ireplace(
            [
                'display: inline-flex',
                'display:inline-flex',
                'display: flex',
                'display:flex',
                'flex-direction: column',
                'flex-direction:column',
                'flex-direction: row',
                'flex-direction:row',
                'justify-content: space-evenly',
                'justify-content:space-evenly',
                'justify-content: space-between',
                'justify-content:space-between',
                'justify-content: space-around',
                'justify-content:space-around',
                'justify-content: flex-start',
                'justify-content:flex-start',
                'justify-content: flex-end',
                'justify-content:flex-end',
                'justify-content: center',
                'justify-content:center',
                'align-items: flex-start',
                'align-items:flex-start',
                'align-items: flex-end',
                'align-items:flex-end',
                'align-items: center',
                'align-items:center',
                'align-items: stretch',
                'align-items:stretch',
                'text-align-last: center',
                'text-align-last:center',
            ],
            [
                'display: -webkit-inline-box',
                'display: -webkit-inline-box',
                'display: -webkit-box',
                'display: -webkit-box',
                '-webkit-box-orient: vertical; -webkit-box-direction: normal',
                '-webkit-box-orient: vertical; -webkit-box-direction: normal',
                '-webkit-box-orient: horizontal; -webkit-box-direction: normal',
                '-webkit-box-orient: horizontal; -webkit-box-direction: normal',
                '-webkit-box-pack: justify',
                '-webkit-box-pack: justify',
                '-webkit-box-pack: justify',
                '-webkit-box-pack: justify',
                '-webkit-box-pack: justify',
                '-webkit-box-pack: justify',
                '-webkit-box-pack: start',
                '-webkit-box-pack: start',
                '-webkit-box-pack: end',
                '-webkit-box-pack: end',
                '-webkit-box-pack: center',
                '-webkit-box-pack: center',
                '-webkit-box-align: start',
                '-webkit-box-align: start',
                '-webkit-box-align: end',
                '-webkit-box-align: end',
                '-webkit-box-align: center',
                '-webkit-box-align: center',
                '-webkit-box-align: stretch',
                '-webkit-box-align: stretch',
                'text-align: center',
                'text-align: center',
            ],
            $html
        );

        $html = $this->replaceFlexGap($html);

        if ($images !== []) {
            $html = strtr($html, $images);
        }

        $styles = <<<CSS
<style id="poster-screenshot-fix">
  html { font-size: 16px !important; }
  html, body {
    margin: 0 !important;
    padding: 0 !important;
    width: {$width}px !important;
    height: {$height}px !important;
  }
  .container, .bg, .container.bg {
    width: {$width}px !important;
    max-width: {$width}px !important;
    height: {$height}px !important;
    min-height: {$height}px !important;
    background-repeat: no-repeat;
    background-position: center center;
  }
  .logo img {
    width: auto !important;
    height: auto !important;
    max-width: 200px !important;
    max-height: 150px !important;
    display: block;
    margin: 16px auto 0 !important;
  }
  footer, .footer {
    position: absolute !important;
    top: auto !important;
    bottom: 18px !important;
    left: 0 !important;
    width: 100% !important;
    z-index: 3;
  }
  .svg-icon, .svgicon {
    display: -webkit-inline-box;
    -webkit-box-align: center;
    -webkit-box-pack: center;
  }
  .wk-gap-h > * + * { margin-left: 16px; }
  .wk-gap-v > * + * { margin-top: 12px; }
</style>
CSS;

        if (stripos($html, '</head>') !== false) {
            return preg_replace('/<\/head>/i', $styles.'</head>', $html, 1) ?? ($styles.$html);
        }

        return $styles.$html;
    }

    /**
     * Old WebKit has no flex gap. Tag the box and space its children.
     */
    protected function replaceFlexGap(string $html): string
    {
        return preg_replace_callback(
            '/<([a-z0-9]+)([^>]*?\sgap\s*:\s*[^>;"]+[^>]*)>/i',
            function (array $match): string {
                $tag = $match[1];
                $attrs = $match[2];
                $vertical = stripos($attrs, 'vertical') !== false;
                $class = $vertical ? 'wk-gap-v' : 'wk-gap-h';
                $attrs = preg_replace('/gap\s*:\s*[^;"]+;?\s*/i', '', $attrs) ?? $attrs;

                if (preg_match('/\sclass\s*=\s*"([^"]*)"/i', $attrs) === 1) {
                    $attrs = preg_replace('/\sclass\s*=\s*"([^"]*)"/i', ' class="$1 '.$class.'"', $attrs, 1) ?? $attrs;
                } else {
                    $attrs .= ' class="'.$class.'"';
                }

                return '<'.$tag.$attrs.'>';
            },
            $html
        ) ?? $html;
    }

    /**
     * @return array{0: string, 1: array<string, string>}
     */
    protected function parkDataUris(string $html): array
    {
        $images = [];
        $html = preg_replace_callback(
            '#data:image/[a-zA-Z0-9.+-]+;base64,[A-Za-z0-9+/=]+#',
            function (array $match) use (&$images): string {
                $token = '___SHOTIMG'.count($images).'___';
                $images[$token] = $match[0];

                return $token;
            },
            $html
        );

        return [$html ?? '', $images];
    }
}
