<?php

namespace App\Services;

/**
 * Single wkhtmltopdf layout layer for all resume-builder templates (resume*.html, latest, professional).
 * Replaces per-template print fixes and destructive global flex CSS rewrites.
 */
class ResumeUniversalPrintFix
{
    public const VERSION = 'resume-universal-print-fix-v1';

    public static function apply(string $html, ?string $format = null): string
    {
        if (stripos($html, 'id="resume-universal-print-fix"') !== false) {
            return $html;
        }

        if ($format !== null && $format !== '' && preg_match('/<html\b/i', $html) && ! preg_match('/data-resume-format=/i', $html)) {
            $html = (string) preg_replace(
                '/<html\b/i',
                '<html data-resume-format="'.htmlspecialchars(basename($format), ENT_QUOTES, 'UTF-8').'"',
                $html,
                1
            );
        }

        $styles = self::stylesheet();

        if (stripos($html, '</head>') !== false) {
            return preg_replace('/<\/head>/i', $styles.'</head>', $html, 1) ?? ($styles.$html);
        }

        return $styles.$html;
    }

    protected static function stylesheet(): string
    {
        return <<<'CSS'
<style id="resume-universal-print-fix">
  /* --- Global print --- */
  html, body {
    -webkit-print-color-adjust: exact !important;
    print-color-adjust: exact !important;
  }
  img {
    max-width: 100%;
  }

  /* --- resume8 / latest-style .resume columns --- */
  .resume {
    display: table !important;
    width: 800px !important;
    max-width: 800px;
    table-layout: fixed;
  }
  .resume .resume_left,
  .resume .resume_right {
    display: table-cell !important;
    vertical-align: top;
  }
  .resume .resume_left {
    width: 280px !important;
  }
  .resume .resume_right {
    width: 520px !important;
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
  html[data-resume-format="latest.html"] .resume,
  html[data-resume-format="latest.html"] body .resume {
    height: 1123px !important;
  }
  html[data-resume-format="latest.html"] .resume .resume_left {
    background: #0bb5f4 !important;
  }

  /* --- Bootstrap-style d-flex two-column (resume30, etc.) --- */
  .content.d-flex,
  .container.d-flex,
  .main.d-flex,
  .main-content.d-flex,
  .header.d-flex,
  .two-column,
  .two-column.d-flex {
    display: block !important;
    width: 100% !important;
    overflow: hidden;
    box-sizing: border-box;
  }
  .content.d-flex::after,
  .header.d-flex::after,
  .two-column::after,
  .main-container::after {
    content: "";
    display: table;
    clear: both;
  }
  .content.d-flex > .left-part,
  .content.d-flex > .right-part,
  .header.d-flex > .left-part,
  .header.d-flex > .right-part,
  .header > .left-part,
  .header > .right-part,
  .content .left-part,
  .content .right-part,
  .header .left-part,
  .header .right-part,
  .container.d-flex > .left-part,
  .container.d-flex > .right-part,
  .container.d-flex > .left,
  .container.d-flex > .right,
  .two-column > .column1,
  .two-column > .column2 {
    float: left;
    box-sizing: border-box;
    vertical-align: top;
  }
  .header > .left-part,
  .content > .left-part,
  .content.d-flex > .left-part,
  .header .left-part,
  .content .left-part {
    width: 35%;
  }
  .header > .right-part,
  .content > .right-part,
  .content.d-flex > .right-part,
  .header .right-part,
  .content .right-part {
    width: 65% !important;
  }
  .two-column > .column1 {
    width: 40%;
  }
  .two-column > .column2 {
    width: 60%;
  }
  html[data-resume-format="resume30.html"] .header,
  .header.d-flex {
    display: block;
    overflow: hidden;
    background: #000 !important;
    color: #fff !important;
    width: 100%;
    box-sizing: border-box;
  }
  html[data-resume-format="resume30.html"] .header .contact-info,
  html[data-resume-format="resume30.html"] .header .font-1,
  html[data-resume-format="resume30.html"] .header .font-3,
  html[data-resume-format="resume30.html"] .header .font-4,
  .header.d-flex .contact-info {
    color: #fff !important;
  }
  html[data-resume-format="resume30.html"] .header .imgDiv,
  .header.d-flex .imgDiv {
    min-height: 220px;
    height: 220px;
    position: relative;
    overflow: hidden;
  }
  html[data-resume-format="resume30.html"] .header .image,
  .header.d-flex .image {
    position: static !important;
    transform: none !important;
    display: block;
    margin: 10px auto;
    max-width: 200px;
    max-height: 200px;
  }

  /* --- resume20 / resume21 main-container --- */
  .main-container {
    display: block;
    overflow: hidden;
    width: 100%;
    box-sizing: border-box;
  }
  .main-container > .left-part {
    float: left;
    width: 35%;
    box-sizing: border-box;
  }
  .main-container > .right-part {
    float: left;
    width: 65%;
    box-sizing: border-box;
  }

  /* --- CSS grid → table (resume25, resume7, …) --- */
  .grid-container,
  .grid-container1,
  .grid-container2 {
    display: table !important;
    width: 100% !important;
    table-layout: fixed;
  }
  .grid-item,
  .grid-item1,
  .grid-item2,
  .grid-item-1,
  .grid-item-2 {
    display: table-cell !important;
    vertical-align: top;
    box-sizing: border-box;
  }
  .grid-container .grid-item-1,
  .grid-container1 .grid-item1:first-child {
    width: 35%;
  }
  .grid-container .grid-item-2,
  .grid-container1 .grid-item1 + .grid-item1 {
    width: 65%;
  }
  .grid-container1 .grid-item1 {
    width: 50%;
  }

  /* --- professional.html --- */
  .resume-main {
    display: block;
    overflow: hidden;
    width: 100%;
    box-sizing: border-box;
    background: #c3266f !important;
    background: linear-gradient(#c3266f, #743578) !important;
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
  .left-box .content-box div,
  .left-box .content-box p {
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

  /* --- resume2.html --- */
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
    background: #C0D8DE !important;
  }
  .years h3 {
    word-break: break-word;
    font-size: 0.95em;
    line-height: 1.2;
  }
  .jobs {
    display: inline-block;
    vertical-align: top;
    width: 80%;
    box-sizing: border-box;
  }
  .containerText {
    display: table !important;
    width: 100%;
    table-layout: fixed;
    padding: 0 2vw 0 4.1vw;
    margin-bottom: 1vw;
    box-sizing: border-box;
  }
  .containerText > .verticalLines,
  .containerText > .verticalLinesEdu {
    display: table-cell !important;
    width: 12px;
    min-height: 48px;
    vertical-align: top;
    border-left: 1px solid black;
    border-right: 1px solid black;
    margin: 0;
  }
  .containerText > div:last-child {
    display: table-cell !important;
    vertical-align: top;
    padding-left: 10px;
  }
  .skill-row {
    overflow: hidden;
    width: 100%;
  }
  .skill-row .skillBar,
  .skills .skillBar {
    float: left;
    border: none;
    border-top: 8px rgb(70, 70, 70) solid;
    height: 0;
    margin: 3px 6px 3px 0;
  }
  #LeftBottom,
  .flexContainer .leftColumn {
    background-color: #C0D8DE !important;
  }
  #rightHeader {
    background-color: rgb(213, 213, 215) !important;
  }
  #Profession {
    position: relative !important;
    top: auto !important;
    margin-top: 8px;
  }
  .flexContainer .leftRow {
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
  .containerHl[style*="flex"] {
    display: block !important;
  }

  /* --- Skill / progress bars --- */
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
</style>
CSS;
    }
}
