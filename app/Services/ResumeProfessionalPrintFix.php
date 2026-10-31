<?php

namespace App\Services;

/** @deprecated Use {@see ResumeUniversalPrintFix} */
class ResumeProfessionalPrintFix
{
    public const VERSION = ResumeUniversalPrintFix::VERSION;

    public static function apply(string $html): string
    {
        return ResumeUniversalPrintFix::apply($html, 'professional.html');
    }
}
