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
}
