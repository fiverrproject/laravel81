<?php

namespace App\Services;

/**
 * wkhtmltoimage binary (bundled in vendor on Linux amd64, or system path).
 */
class WkhtmltoimagePathResolver
{
    /**
     * @var list<string>
     */
    protected const CANDIDATES = [
        '/usr/local/bin/wkhtmltoimage',
        '/usr/bin/wkhtmltoimage',
    ];

    public static function tryResolve(): ?string
    {
        $configured = trim((string) config('services.wkhtmltoimage.path', ''));
        if ($configured !== '' && self::isUsable($configured)) {
            return $configured;
        }

        foreach (self::bundledPaths() as $path) {
            if (self::isUsable($path)) {
                return $path;
            }
        }

        $pdf = WkhtmltopdfPathResolver::tryResolve();
        if ($pdf !== null) {
            $sibling = dirname($pdf).'/wkhtmltoimage';
            if (self::isUsable($sibling)) {
                return $sibling;
            }
        }

        foreach (self::CANDIDATES as $path) {
            if (self::isUsable($path)) {
                return $path;
            }
        }

        return null;
    }

    /**
     * @return list<string>
     */
    protected static function bundledPaths(): array
    {
        $paths = [];
        $vendor = base_path('vendor/h4cc/wkhtmltoimage-amd64/bin/wkhtmltoimage-amd64');
        if (self::isUsable($vendor)) {
            $paths[] = $vendor;
        }
        $vendorDir = base_path('vendor/h4cc/wkhtmltoimage-amd64/bin');
        if (is_dir($vendorDir)) {
            foreach (glob($vendorDir.'/wkhtmltoimage*') ?: [] as $file) {
                if (is_string($file)) {
                    $paths[] = $file;
                }
            }
        }

        return $paths;
    }

    public static function isUsable(string $path): bool
    {
        return $path !== '' && is_file($path) && is_executable($path);
    }
}
