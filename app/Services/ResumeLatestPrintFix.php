<?php

namespace App\Services;

/** @deprecated Use {@see ResumeUniversalPrintFix} */
class ResumeLatestPrintFix
{
    public const VERSION = ResumeUniversalPrintFix::VERSION;

    public static function apply(string $html): string
    {
        return ResumeUniversalPrintFix::apply($html, 'latest.html');
    }
}
