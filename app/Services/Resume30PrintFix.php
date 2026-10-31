<?php

namespace App\Services;

/** @deprecated Use {@see ResumeUniversalPrintFix} */
class Resume30PrintFix
{
    public const VERSION = ResumeUniversalPrintFix::VERSION;

    public static function apply(string $html): string
    {
        return ResumeUniversalPrintFix::apply($html, 'resume30.html');
    }
}
