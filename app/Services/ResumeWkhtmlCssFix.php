<?php

namespace App\Services;

/**
 * @deprecated Use ResumeUniversalPrintFix — kept for version metadata only.
 */
class ResumeWkhtmlCssFix
{
    public const VERSION = ResumeUniversalPrintFix::VERSION;

    public static function apply(string $html): string
    {
        return ResumeUniversalPrintFix::apply($html);
    }
}
