<?php

namespace App\Services;

/**
 * wkhtmltopdf fixes for resume2.html (flex columns + verticalLines row).
 * Global ResumeWkhtmlCssFix breaks .containerText flex and causes barcode artifacts.
 */
class Resume2PrintFix
{
    public const VERSION = 'resume2-print-fix-v2';

    public static function apply(string $html): string
    {
        if (stripos($html, 'id="resume2-print-fix"') !== false) {
            return $html;
        }

        $styles = <<<'CSS'
<style id="resume2-print-fix">
  .flexContainer {
    display: block;
    width: 100%;
    overflow: hidden;
    box-sizing: border-box;
  }
  .flexContainer::after {
    content: "";
    display: table;
    clear: both;
  }
  .leftColumn {
    float: left;
    width: 25%;
    box-sizing: border-box;
  }
  .rightColumn {
    float: left;
    width: 75%;
    box-sizing: border-box;
  }
  .containerHl {
    display: block;
    width: 100%;
    overflow: hidden;
    padding: 0 2vw;
    box-sizing: border-box;
  }
  .years {
    display: inline-block;
    vertical-align: top;
    width: 22%;
    min-width: 130px;
    box-sizing: border-box;
  }
  .jobs {
    display: inline-block;
    vertical-align: top;
    width: 80%;
    box-sizing: border-box;
  }
  .containerText {
    display: table;
    width: 100%;
    table-layout: fixed;
    padding: 0 2vw 0 4.1vw;
    margin-bottom: 1vw;
    box-sizing: border-box;
  }
  .containerText > .verticalLines,
  .containerText > .verticalLinesEdu {
    display: table-cell;
    width: 12px;
    min-height: 48px;
    vertical-align: top;
    border-left: 1px solid black;
    border-right: 1px solid black;
    margin: 0;
  }
  .containerText > div:last-child {
    display: table-cell;
    vertical-align: top;
    padding-left: 10px;
    width: auto;
  }
  .skills div[style*="flex"],
  .skills div[style*="Flex"] {
    display: block !important;
    overflow: hidden;
    width: 100%;
  }
  .skillBar {
    display: block;
    float: left;
    clear: none;
    border-top: 8px rgb(70, 70, 70) solid;
    border-radius: 10px;
    height: 0;
    margin: 3px 6px 3px 0;
  }
  .containerHl[style*="flex"] {
    display: block !important;
  }
  #LeftBottom,
  .leftColumn {
    background-color: #C0D8DE !important;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
  }
  #rightHeader {
    background-color: rgb(213, 213, 215) !important;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
  }
  .years {
    background: #C0D8DE !important;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
  }
  .years h3 {
    word-break: break-word;
    font-size: 0.95em;
    line-height: 1.2;
  }
  #Profession {
    position: relative !important;
    top: auto !important;
    margin-top: 8px;
  }
  .leftRow {
    position: relative;
  }
  #dotContainer.over {
    position: relative !important;
    bottom: auto !important;
    margin: 6px auto;
  }
  .HeaderHr {
    position: relative !important;
    left: auto !important;
    width: 30%;
    display: inline-block;
    margin: 4px 2%;
  }
</style>
CSS;

        if (stripos($html, '</head>') !== false) {
            return preg_replace('/<\/head>/i', $styles.'</head>', $html, 1) ?? ($styles.$html);
        }

        return $styles.$html;
    }
}
