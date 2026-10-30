<?php

namespace App\Services;

use HeadlessChromium\AutoDiscover;
use RuntimeException;

/**
 * Locate headless Chrome/Chromium on Linux (Hostinger, VPS, local dev).
 */
class ChromePathResolver
{
    /**
     * Common paths on CloudLinux / Hostinger / Ubuntu.
     *
     * @var list<string>
     */
    protected const CANDIDATES = [
        '/usr/bin/google-chrome-stable',
        '/usr/bin/google-chrome',
        '/usr/bin/chromium-browser',
        '/usr/bin/chromium',
        '/snap/bin/chromium',
        '/opt/google/chrome/google-chrome',
        '/opt/google/chrome/chrome',
    ];

    /**
     * @return array{path: string, tried: list<string>}
     */
    public static function resolve(): array
    {
        $tried = [];

        $configured = trim((string) config('services.chrome.path', ''));
        if ($configured !== '') {
            $tried[] = $configured;
            if (self::isUsableBinary($configured)) {
                return ['path' => $configured, 'tried' => $tried];
            }
        }

        foreach (self::CANDIDATES as $candidate) {
            if (in_array($candidate, $tried, true)) {
                continue;
            }
            $tried[] = $candidate;
            if (self::isUsableBinary($candidate)) {
                return ['path' => $candidate, 'tried' => $tried];
            }
        }

        $discovered = trim((new AutoDiscover)->guessChromeBinaryPath());
        if ($discovered !== '' && ! in_array($discovered, $tried, true)) {
            $tried[] = $discovered;
            if (self::isUsableBinary($discovered)) {
                return ['path' => $discovered, 'tried' => $tried];
            }
        }

        foreach (['google-chrome-stable', 'google-chrome', 'chromium-browser', 'chromium', 'chrome'] as $cmd) {
            $fromPath = self::which($cmd);
            if ($fromPath === null || in_array($fromPath, $tried, true)) {
                continue;
            }
            $tried[] = $fromPath;
            if (self::isUsableBinary($fromPath)) {
                return ['path' => $fromPath, 'tried' => $tried];
            }
        }

        throw new RuntimeException(
            'Headless Chrome/Chromium not found on this server. '
            .'Install Chromium or set CHROME_PATH in .env to the full path. '
            .'Run: php artisan resume:detect-chrome. Tried: '.implode(', ', $tried)
        );
    }

    public static function isUsableBinary(string $path): bool
    {
        if ($path === '' || $path === 'chrome') {
            return false;
        }

        return is_file($path) && is_executable($path);
    }

    protected static function which(string $command): ?string
    {
        $output = @\shell_exec('command -v '.\escapeshellarg($command).' 2>/dev/null');

        if (! is_string($output)) {
            return null;
        }

        $path = trim(explode("\n", $output)[0]);

        return $path !== '' ? $path : null;
    }
}
