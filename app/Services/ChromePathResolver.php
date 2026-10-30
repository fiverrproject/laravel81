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

    /**
     * @return array{path: string, tried: list<string>}
     */
    public static function resolve(): array
    {
        $tried = [];

        foreach (self::orderedCandidates() as $candidate) {
            if (in_array($candidate, $tried, true)) {
                continue;
            }
            $tried[] = $candidate;
            if (self::isUsableBinary($candidate)) {
                return ['path' => $candidate, 'tried' => $tried];
            }
        }

        throw new RuntimeException(
            'Headless Chrome/Chromium not found. Set CHROME_PATH in server/.env to the full binary path. '
            .'On SSH run: ls -la /usr/bin/google-chrome* /usr/bin/chromium* 2>/dev/null. '
            .'Tried: '.implode(', ', $tried)
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
