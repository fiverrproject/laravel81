<?php

namespace App\Services;

/**
 * wkhtmltopdf/WebKit fixes for resume30 (float columns — flex/grid break in Qt WebKit).
 */
class Resume30PrintFix
{
    public const VERSION = 'resume30-print-fix-v2';

    public static function apply(string $html): string
    {
        if (stripos($html, 'id="resume30-print-fix"') !== false) {
            return $html;
        }

        $styles = <<<'CSS'
<style id="resume30-print-fix">
  .header {
    display: block;
    overflow: hidden;
    background: #000 !important;
    color: #fff !important;
    width: 100%;
    box-sizing: border-box;
  }
  .header::after {
    content: "";
    display: table;
    clear: both;
  }
  .header .left-part {
    float: left;
    width: 35%;
    box-sizing: border-box;
  }
  .header .right-part {
    float: left;
    width: 65% !important;
    box-sizing: border-box;
    color: #fff !important;
  }
  .header .contact-info,
  .header .font-1,
  .header .font-3,
  .header .font-4 {
    color: #fff !important;
  }
  .header .imgDiv {
    min-height: 220px;
    height: 220px;
    position: relative;
    overflow: hidden;
  }
  .header .image {
    position: static !important;
    transform: none !important;
    display: block;
    margin: 10px auto;
    max-width: 200px;
    max-height: 200px;
    width: auto;
    height: auto;
  }
  .content.d-flex {
    display: block;
    overflow: hidden;
    width: 100%;
    box-sizing: border-box;
  }
  .content.d-flex::after {
    content: "";
    display: table;
    clear: both;
  }
  .content .left-part {
    float: left;
    width: 35%;
    box-sizing: border-box;
  }
  .content .right-part {
    float: left;
    width: 65% !important;
    box-sizing: border-box;
  }
  .grid-container1 {
    display: block;
    width: 100%;
    overflow: hidden;
  }
  .grid-container1 .grid-item1 {
    float: left;
    width: 50%;
    box-sizing: border-box;
  }
  .grid-container1::after {
    content: "";
    display: table;
    clear: both;
  }
  .progress {
    display: block;
    overflow: hidden;
    width: 95%;
  }
  .progress-bar {
    display: block;
    float: left;
    height: 1rem;
    min-width: 0;
  }
</style>
CSS;

        if (stripos($html, '</head>') !== false) {
            return preg_replace('/<\/head>/i', $styles.'</head>', $html, 1) ?? ($styles.$html);
        }

        return $styles.$html;
    }
}
