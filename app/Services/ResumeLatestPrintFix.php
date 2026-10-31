<?php

namespace App\Services;

/**
 * wkhtmltopdf/WebKit layout for latest.html (flex 280+520 columns).
 */
class ResumeLatestPrintFix
{
    public const VERSION = 'resume-latest-print-fix-v1';

    public static function apply(string $html): string
    {
        if (stripos($html, 'id="resume-latest-print-fix"') !== false) {
            return $html;
        }

        $styles = <<<'CSS'
<style id="resume-latest-print-fix">
  html, body {
    margin: 0;
    padding: 0;
    background: #fff;
  }
  html, body, .resume {
    height: 1123px;
  }
  .resume {
    display: table !important;
    width: 800px !important;
    max-width: 800px;
    table-layout: fixed;
    height: 1123px !important;
  }
  .resume .resume_left {
    display: table-cell !important;
    width: 280px !important;
    vertical-align: top;
    background: #0bb5f4 !important;
  }
  .resume .resume_right {
    display: table-cell !important;
    width: 520px !important;
    vertical-align: top;
    background: #fff !important;
  }
  .resume .resume_left .resume_profile {
    width: 280px;
    height: 280px;
    overflow: hidden;
  }
  .resume .resume_left .resume_profile img {
    display: block;
    width: 280px;
    height: 280px;
  }
  .resume .resume_left .resume_skills ul li progress {
    width: 70%;
    height: 8px;
  }
</style>
CSS;

        if (stripos($html, '</head>') !== false) {
            return preg_replace('/<\/head>/i', $styles.'</head>', $html, 1) ?? ($styles.$html);
        }

        return $styles.$html;
    }
}
