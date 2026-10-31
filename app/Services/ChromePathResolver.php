<?php

namespace App\Services;

use RuntimeException;

/**
 * Locate headless Chrome/Chromium without shell_exec (Hostinger disables it).
 */
class ChromePathResolver
{
    /**
     * @var list<string>
     */
    protected const CANDIDATES = [
        '/usr/bin/google-chrome-stable',
        '/usr/bin/google-chrome',
        '/usr/bin/chromium-browser',
        '/usr/bin/chromium',
        '/usr/bin/chrome',
        '/snap/bin/chromium',
        '/opt/google/chrome/google-chrome',
        '/opt/google/chrome/chrome',
        '/opt/chromium.org/chromium/chromium',
    ];

    public static function tryResolve(): ?string
    {
        foreach (self::orderedCandidates() as $candidate) {
            if (self::isUsableBinary($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * @return array{path: string, tried: list<string>}
     */
    public static function resolve(): array
    {
        $tried = self::orderedCandidates();
        $path = self::tryResolve();

        if ($path !== null) {
            return ['path' => $path, 'tried' => $tried];
        }

        throw new RuntimeException(
            'Headless Chrome/Chromium not found. Use PDF_ENGINE=dompdf in .env (Hostinger shared hosting), '
            .'or set CHROME_PATH if you have Chromium on a VPS. Tried: '.implode(', ', $tried)
        );
    }

    /**
     * @return list<string>
     */
    protected static function orderedCandidates(): array
    {
        $list = [];

        $configured = trim((string) config('services.chrome.path', ''));
        if ($configured !== '') {
            $list[] = $configured;
        }

        $list = array_merge($list, self::CANDIDATES, self::globBinaries());

        return array_values(array_unique($list));
    }

    /**
     * @return list<string>
     */
    protected static function globBinaries(): array
    {
        $found = [];
        $patterns = [
            '/usr/bin/google-chrome*',
            '/usr/bin/chromium*',
            '/opt/google/chrome/*',
        ];

        foreach ($patterns as $pattern) {
            $matches = glob($pattern);
            if (! is_array($matches)) {
                continue;
            }
            foreach ($matches as $path) {
                if (is_string($path) && is_file($path)) {
                    $found[] = $path;
                }
            }
        }

        return $found;
    }

    public static function isUsableBinary(string $path): bool
    {
        if ($path === '' || $path === 'chrome') {
            return false;
        }

        return is_file($path) && is_executable($path);
    }
}
