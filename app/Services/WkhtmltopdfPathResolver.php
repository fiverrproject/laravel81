<?php

namespace App\Services;

/**
 * wkhtmltopdf binary (bundled in vendor on Linux amd64, or system path).
 */
class WkhtmltopdfPathResolver
{
    /**
     * @var list<string>
     */
    protected const CANDIDATES = [
        '/usr/local/bin/wkhtmltopdf',
        '/usr/bin/wkhtmltopdf',
    ];

    public static function tryResolve(): ?string
    {
        $configured = trim((string) config('services.wkhtmltopdf.path', ''));
        if ($configured !== '' && self::isUsable($configured)) {
            return $configured;
        }

        foreach (self::bundledPaths() as $path) {
            if (self::isUsable($path)) {
                return $path;
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
        $vendor = base_path('vendor/h4cc/wkhtmltopdf-amd64/bin/wkhtmltopdf-amd64');
        if (self::isUsable($vendor)) {
            $paths[] = $vendor;
        }
        $vendorDir = base_path('vendor/h4cc/wkhtmltopdf-amd64/bin');
        if (is_dir($vendorDir)) {
            foreach (glob($vendorDir.'/wkhtmltopdf*') ?: [] as $file) {
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
