<?php

namespace App\Services;

/**
 * wkhtmltopdf fixes for professional.html (float columns + gradient sidebar).
 */
class ResumeProfessionalPrintFix
{
    public const VERSION = 'resume-professional-print-fix-v1';

    public static function apply(string $html): string
    {
        if (stripos($html, 'id="resume-professional-print-fix"') !== false) {
            return $html;
        }

        $styles = <<<'CSS'
<style id="resume-professional-print-fix">
  .resume-main {
    display: block;
    overflow: hidden;
    width: 100%;
    box-sizing: border-box;
    background: #c3266f !important;
    background: linear-gradient(#c3266f, #743578) !important;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
  }
  .resume-main::after {
    content: "";
    display: table;
    clear: both;
  }
  .left-box {
    float: left;
    width: 35%;
    box-sizing: border-box;
    min-height: 1120px;
  }
  .right-box {
    float: right;
    width: 65%;
    box-sizing: border-box;
    background: #fff !important;
    margin: 50px 0;
    border-radius: 50px 0 0 50px;
    padding: 30px 50px;
    box-shadow: -7px 2px 15px 2px rgba(84, 88, 93, 0.32);
  }
  .content-box h2,
  .content-box .p1,
  .content-box .p2,
  .content-box .inp,
  .left-box div,
  .left-box p {
    color: #fff !important;
  }
  .lang-progress {
    background: #a5a5a5;
    border-radius: 13px;
    height: 8px;
    width: 100%;
    margin: 4px 0 10px;
    overflow: hidden;
  }
  .lang-progress-fill {
    background: #fff;
    height: 100%;
    border-radius: 9px;
  }
  progress {
    display: block;
    width: 100%;
    height: 8px;
    margin: 4px 0 10px;
    -webkit-appearance: none;
    appearance: none;
    border: none;
    border-radius: 13px;
    background: #a5a5a5;
  }
  progress::-webkit-progress-bar {
    background: #a5a5a5;
    border-radius: 13px;
  }
  progress::-webkit-progress-value {
    background: #fff;
    border-radius: 9px;
  }
  .profile img {
    display: block;
    max-width: 100%;
    height: auto;
  }
</style>
CSS;

        if (stripos($html, '</head>') !== false) {
            return preg_replace('/<\/head>/i', $styles.'</head>', $html, 1) ?? ($styles.$html);
        }

        return $styles.$html;
    }
}
