<?php

namespace App\Services;

/**
 * Single wkhtmltopdf layout layer for all resume-builder templates (resume*.html, latest, professional).
 * Replaces per-template print fixes and destructive global flex CSS rewrites.
 */
class ResumeUniversalPrintFix
{
    public const VERSION = 'resume-universal-print-fix-v28';

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
    font-size: 18px !important;
  }
  p, li, td, th {
    font-size: 18px;
  }
  h2 {
    font-size: 22px;
  }
  h3 {
    font-size: 20px;
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
  html[data-resume-format="resume2.html"],
  html[data-resume-format="resume2.html"] body {
    background: #fff !important;
  }
  html[data-resume-format="resume2.html"] body {
    margin: 0 !important;
    padding: 12mm 10mm 14mm 10mm !important;
    box-sizing: border-box;
  }
  html[data-resume-format="resume2.html"] .flexContainer,
  .flexContainer {
    display: table !important;
    width: 100% !important;
    table-layout: fixed;
    overflow: visible;
    box-sizing: border-box;
  }
  html[data-resume-format="resume2.html"] .leftColumn,
  .flexContainer > .leftColumn {
    display: table-cell !important;
    float: none !important;
    width: 28% !important;
    vertical-align: top;
    background-color: #C0D8DE !important;
    padding: 0 !important;
  }
  html[data-resume-format="resume2.html"] .rightColumn,
  .flexContainer > .rightColumn {
    display: table-cell !important;
    float: none !important;
    width: 72% !important;
    vertical-align: top;
    padding: 0 !important;
  }
  html[data-resume-format="resume2.html"] #LeftBottom {
    padding: 12px 16px 24px 16px !important;
    box-sizing: border-box;
  }
  html[data-resume-format="resume2.html"] .rightRow {
    padding: 18px 22px 10px 22px !important;
    box-sizing: border-box;
  }
  html[data-resume-format="resume2.html"] .containerHl {
    padding: 0 22px 6px 22px !important;
  }
  html[data-resume-format="resume2.html"] .containerText {
    padding: 0 22px 10px 36px !important;
    margin-bottom: 8px !important;
  }
  html[data-resume-format="resume2.html"] .leftColumn .font-size,
  html[data-resume-format="resume2.html"] .leftColumn span.font-size {
    display: block;
    padding: 3px 0;
    word-break: break-word !important;
  }
  html[data-resume-format="resume2.html"] .leftColumn hr {
    margin: 4px 0 !important;
    width: 100%;
  }
  html[data-resume-format="resume2.html"] .leftColumn .skills {
    padding: 6px 0 !important;
    margin-bottom: 4px !important;
  }
  html[data-resume-format="resume2.html"] .leftColumn h2 {
    margin: 10px 0 6px !important;
  }
  html[data-resume-format="resume2.html"] .leftColumn img,
  .flexContainer .leftColumn img {
    display: block;
    width: 100% !important;
    max-width: 100% !important;
    height: 225px !important;
    object-fit: cover;
  }
  .containerHl {
    display: block !important;
    width: 100%;
    overflow: hidden;
    padding: 0 24px;
    box-sizing: border-box;
  }
  html[data-resume-format="resume2.html"] .years,
  .flexContainer .years {
    display: block !important;
    width: 90% !important;
    min-width: 0 !important;
    box-sizing: border-box;
    background: #C0D8DE !important;
    border-radius: 12px;
    text-align: center;
  }
  .years h3 {
    word-break: break-word;
    font-size: 0.95em;
    line-height: 1.2;
  }
  .jobs {
    display: block;
    width: 100%;
    box-sizing: border-box;
  }
  .containerText {
    display: block !important;
    width: 100%;
    padding: 0 24px 0 40px;
    margin-bottom: 12px;
    overflow: hidden;
    box-sizing: border-box;
  }
  html[data-resume-format="resume2.html"] .containerText > .verticalLines,
  html[data-resume-format="resume2.html"] .containerText > .verticalLinesEdu {
    display: block !important;
    float: left;
    width: 3px !important;
    min-height: 56px;
    height: 56px;
    vertical-align: top;
    border-left: 1px solid #000;
    border-right: 1px solid #000;
    margin: 0 1px;
    padding: 0;
  }
  html[data-resume-format="resume2.html"] .containerText > div:last-child {
    display: block !important;
    float: left;
    overflow: hidden;
    vertical-align: top;
    padding-left: 10px !important;
    width: 78% !important;
    box-sizing: border-box;
  }
  .skill-row {
    display: table !important;
    width: 100% !important;
    table-layout: fixed;
    overflow: visible;
  }
  html[data-resume-format="resume2.html"] .skill-bar-wrap {
    display: table-cell !important;
    width: 78% !important;
    vertical-align: middle;
  }
  html[data-resume-format="resume2.html"] .skill-pct {
    display: table-cell !important;
    width: 22% !important;
    vertical-align: middle;
    white-space: nowrap !important;
    padding-left: 4px !important;
  }
  html[data-resume-format="resume2.html"] .skill-track {
    width: 100% !important;
    height: 8px !important;
    background: #c8c8c8 !important;
    border-radius: 10px;
    overflow: hidden;
  }
  html[data-resume-format="resume2.html"] .skill-row .skillBar,
  html[data-resume-format="resume2.html"] .skills .skillBar {
    display: block !important;
    float: none !important;
    border: none !important;
    height: 8px !important;
    margin: 0 !important;
    max-width: 100%;
    background: rgb(70, 70, 70) !important;
    border-radius: 10px;
  }
  .skill-row .skillBar,
  .skills .skillBar {
    display: block !important;
    float: none !important;
    border: none;
    height: 8px;
    margin: 4px 0 !important;
    max-width: 100%;
  }
  #LeftBottom,
  .flexContainer .leftColumn {
    background-color: #C0D8DE !important;
  }
  #rightHeader {
    background-color: rgb(213, 213, 215) !important;
  }
  html[data-resume-format="resume2.html"] #Profession {
    position: relative !important;
    top: auto !important;
    margin-top: 8px;
    font-size: 18px !important;
  }
  .flexContainer .leftRow {
    position: relative;
  }
  #dotContainer.over {
    position: relative !important;
    bottom: auto !important;
    margin: 6px auto;
  }
  html[data-resume-format="resume2.html"] .HeaderHr {
    position: relative !important;
    left: auto !important;
    width: 30%;
    display: inline-block;
    margin: 4px 2%;
  }
  html[data-resume-format="resume2.html"] .signature-block {
    text-align: right !important;
    padding: 20px 22px 8px 22px !important;
  }
  html[data-resume-format="resume2.html"] .signature-block .left1sing {
    display: block !important;
    text-align: right !important;
    margin: 0 0 8px !important;
  }
  html[data-resume-format="resume2.html"] .signature-img,
  html[data-resume-format="resume2.html"] img[alt="Signature"] {
    display: block !important;
    float: none !important;
    margin-left: auto !important;
    margin-right: 0 !important;
    width: 120px !important;
    height: 48px !important;
    max-width: 120px !important;
    max-height: 48px !important;
    object-fit: contain !important;
  }

  /* --- resume3.html: pill headers flush left, wrap objective, fixed signature --- */
  html[data-resume-format="resume3.html"],
  html[data-resume-format="resume3.html"] body {
    background: #fff !important;
    margin: 0 !important;
    padding: 0 !important;
  }
  html[data-resume-format="resume3.html"] .myResume {
    width: 100% !important;
    max-width: 100% !important;
    padding: 18px 0 28px 0 !important;
    margin: 0 !important;
    box-sizing: border-box;
    overflow: hidden;
    text-shadow: none !important;
  }
  html[data-resume-format="resume3.html"] .myResume-Contact-Info {
    float: left;
    width: 62% !important;
    margin: 8px 0 16px 0 !important;
    padding-left: 24px !important;
    box-sizing: border-box;
  }
  html[data-resume-format="resume3.html"] .myResume-Profile-Pic {
    float: right;
    width: 34% !important;
    margin: 8px 18px 16px 0 !important;
    text-align: right;
  }
  html[data-resume-format="resume3.html"] .myResume-Profile-Pic img {
    width: 140px !important;
    height: 155px !important;
    max-width: 140px !important;
    object-fit: cover;
  }
  html[data-resume-format="resume3.html"] h1.title {
    position: static !important;
    left: auto !important;
    display: block !important;
    width: 86% !important;
    max-width: 86% !important;
    margin: 12px 0 10px 0 !important;
    padding: 8px 18px 8px 24px !important;
    box-sizing: border-box;
    background-color: #ccc !important;
    color: #fff !important;
    border-radius: 0 40px 40px 0 !important;
    text-align: left !important;
    text-transform: uppercase;
    text-shadow: 1px 1px 3px #000 !important;
    -webkit-print-color-adjust: exact !important;
    print-color-adjust: exact !important;
  }
  html[data-resume-format="resume3.html"] .myResume-Profile-Intro,
  html[data-resume-format="resume3.html"] .myResume-Skills,
  html[data-resume-format="resume3.html"] .myResume-Education,
  html[data-resume-format="resume3.html"] .myResume-Experience {
    width: 100% !important;
    max-width: 100% !important;
    margin: 0 0 8px 0 !important;
    overflow: hidden;
    box-sizing: border-box;
  }
  html[data-resume-format="resume3.html"] .myResume-Profile-Intro > p,
  html[data-resume-format="resume3.html"] .objective-text {
    display: block !important;
    width: 680px !important;
    max-width: 680px !important;
    margin: 0 24px 10px 24px !important;
    padding: 0 !important;
    white-space: normal !important;
    word-break: normal !important;
    word-wrap: break-word !important;
    overflow-wrap: break-word !important;
    text-align: justify !important;
    line-height: 1.4em !important;
  }
  html[data-resume-format="resume3.html"] .word-break {
    word-break: normal !important;
    word-wrap: break-word !important;
    overflow-wrap: break-word !important;
    white-space: normal !important;
  }
  html[data-resume-format="resume3.html"] .myResume-Education > ul,
  html[data-resume-format="resume3.html"] .myResume-Experience > ul,
  html[data-resume-format="resume3.html"] .myResume-Education p,
  html[data-resume-format="resume3.html"] .myResume-Experience p {
    margin-left: 24px;
    margin-right: 24px;
    width: auto !important;
    max-width: 680px;
    word-wrap: break-word !important;
    overflow-wrap: break-word !important;
    white-space: normal !important;
  }
  html[data-resume-format="resume3.html"] table.skill-line {
    width: 680px !important;
    margin: 2px 24px 4px 24px !important;
    border-collapse: collapse;
    table-layout: fixed;
  }
  html[data-resume-format="resume3.html"] table.skill-line td.skill-name {
    text-align: left;
    vertical-align: top;
    padding: 1px 10px 1px 0;
  }
  html[data-resume-format="resume3.html"] table.skill-line td.skill-pct {
    width: 72px !important;
    text-align: right !important;
    white-space: nowrap !important;
    vertical-align: top;
  }

  /* --- resume4.html: dotted header box + always-show skill % --- */
  html[data-resume-format="resume4.html"],
  html[data-resume-format="resume4.html"] body {
    background: #fff !important;
    margin: 0 !important;
    padding: 0 !important;
    overflow: visible !important;
  }
  html[data-resume-format="resume4.html"] .resume4-wrap {
    padding: 16px 14px 20px 14px !important;
    margin: 0 !important;
    box-sizing: border-box;
    overflow: visible !important;
  }
  html[data-resume-format="resume4.html"] .header {
    border: 2px dotted #000 !important;
    border-style: dotted !important;
    border-width: 2px !important;
    border-color: #000 !important;
    -webkit-border-radius: 20px !important;
    border-radius: 20px !important;
    padding: 16px 14px 18px 14px !important;
    margin: 4px 2px 14px 2px !important;
    background: #fff !important;
    background-color: #fff !important;
    text-align: center !important;
    overflow: visible !important;
    width: auto !important;
    max-width: 100% !important;
    box-sizing: border-box !important;
    -webkit-print-color-adjust: exact !important;
    print-color-adjust: exact !important;
  }
  html[data-resume-format="resume4.html"] .header h2,
  html[data-resume-format="resume4.html"] .header p {
    margin: 0;
    padding: 1px 8px;
  }

  /* --- resume5.html: table columns, solid gold/dark (wkhtml no flex/grid/8-digit hex) --- */
  html[data-resume-format="resume5.html"],
  html[data-resume-format="resume5.html"] body {
    background: #fff !important;
    margin: 0 !important;
    padding: 0 !important;
    font-size: 18px !important;
  }
  html[data-resume-format="resume5.html"] .r5-left h3 {
    font-size: 22px !important;
  }
  html[data-resume-format="resume5.html"] .r5-left p {
    font-size: 18px !important;
  }
  html[data-resume-format="resume5.html"] .r5-name h1 {
    font-size: 34px !important;
  }
  html[data-resume-format="resume5.html"] .r5-name h3 {
    font-size: 22px !important;
  }
  html[data-resume-format="resume5.html"] .r5-objective h3 {
    font-size: 22px !important;
  }
  html[data-resume-format="resume5.html"] .r5-objective p {
    font-size: 18px !important;
    line-height: 1.4 !important;
  }
  html[data-resume-format="resume5.html"] .r5-card-title {
    font-size: 22px !important;
  }
  html[data-resume-format="resume5.html"] .r5-card-body p {
    font-size: 18px !important;
    line-height: 1.35 !important;
  }
  html[data-resume-format="resume5.html"] .r5-achv-title {
    font-size: 18px !important;
  }
  html[data-resume-format="resume5.html"] .r5-achv p {
    font-size: 18px !important;
  }
  html[data-resume-format="resume5.html"] .r5-page {
    width: 100% !important;
    height: 1120px !important;
    border-collapse: collapse !important;
    table-layout: fixed !important;
  }
  html[data-resume-format="resume5.html"] .r5-left {
    width: 28% !important;
    height: 1120px !important;
    background-color: #2d2d2d !important;
    color: #ffb629 !important;
    vertical-align: top !important;
    padding: 12px 8px 30px 10px !important;
    -webkit-print-color-adjust: exact !important;
  }
  html[data-resume-format="resume5.html"] .r5-right {
    width: 72% !important;
    background-color: #fff !important;
    vertical-align: top !important;
  }
  html[data-resume-format="resume5.html"] .r5-photo {
    display: block !important;
    width: 150px !important;
    height: 150px !important;
    max-width: 150px !important;
    border: 5px solid #fff !important;
    border-radius: 50% !important;
    margin: 16px auto 12px auto !important;
    object-fit: cover;
  }
  html[data-resume-format="resume5.html"] .r5-name {
    background-color: #ffb629 !important;
    border-radius: 0 40px 0 0 !important;
    -webkit-print-color-adjust: exact !important;
  }
  html[data-resume-format="resume5.html"] .r5-bar-dark,
  html[data-resume-format="resume5.html"] .r5-card-body,
  html[data-resume-format="resume5.html"] .r5-achv-title {
    background-color: #2d2d2d !important;
    -webkit-print-color-adjust: exact !important;
  }
  html[data-resume-format="resume5.html"] .r5-bar-gold,
  html[data-resume-format="resume5.html"] .r5-card-title,
  html[data-resume-format="resume5.html"] .r5-fill,
  html[data-resume-format="resume5.html"] .r5-achv {
    background-color: #ffb629 !important;
    -webkit-print-color-adjust: exact !important;
  }
  html[data-resume-format="resume5.html"] .r5-cards {
    width: 100% !important;
    border-collapse: separate !important;
    border-spacing: 10px 14px !important;
  }
  html[data-resume-format="resume5.html"] .r5-block {
    width: 100% !important;
    border-collapse: collapse !important;
  }
  html[data-resume-format="resume5.html"] .r5-title-pad {
    padding: 0 16px !important;
    text-align: center !important;
    vertical-align: bottom !important;
  }
  html[data-resume-format="resume5.html"] .r5-card-title {
    display: block !important;
    width: 100% !important;
    margin: 0 !important;
    padding: 8px 8px !important;
    text-align: center !important;
    border: 4px solid #fff !important;
    box-sizing: border-box !important;
  }
  html[data-resume-format="resume5.html"] .r5-card-body {
    padding: 16px 10px 14px 10px !important;
    min-height: 170px;
    vertical-align: top !important;
  }
  html[data-resume-format="resume5.html"] .r5-track {
    background-color: #eee !important;
    height: 14px !important;
    overflow: hidden !important;
    width: 96% !important;
  }
  html[data-resume-format="resume5.html"] .r5-fill {
    height: 14px !important;
    min-height: 14px;
    font-size: 15px !important;
    line-height: 14px !important;
  }
  html[data-resume-format="resume5.html"] .r5-sign,
  html[data-resume-format="resume5.html"] img[alt="Signature"] {
    width: 120px !important;
    height: 48px !important;
    max-width: 120px !important;
    max-height: 48px !important;
    object-fit: contain !important;
  }

  /* --- resume6.html: two-column gray layout, black header, overlapping photo --- */
  html[data-resume-format="resume6.html"],
  html[data-resume-format="resume6.html"] body {
    margin: 0 !important;
    padding: 0 !important;
    background: #fff !important;
    font-size: 18px !important;
  }
  html[data-resume-format="resume6.html"] .r6-page {
    width: 100% !important;
    border-collapse: collapse !important;
    table-layout: fixed !important;
    border: 1px solid #000 !important;
  }
  html[data-resume-format="resume6.html"] .r6-header {
    background-color: #000 !important;
    color: #fff !important;
    border-top: 12px solid #a6a6a6 !important;
    -webkit-print-color-adjust: exact !important;
  }
  html[data-resume-format="resume6.html"] .r6-name-cell {
    background-color: #000 !important;
    color: #fff !important;
    -webkit-print-color-adjust: exact !important;
  }
  html[data-resume-format="resume6.html"] .r6-name {
    font-size: 34px !important;
    color: #fff !important;
    margin: 0 !important;
  }
  html[data-resume-format="resume6.html"] .r6-job {
    font-size: 18px !important;
    letter-spacing: 5px !important;
    color: #fff !important;
  }
  html[data-resume-format="resume6.html"] .r6-photo {
    display: block !important;
    width: 150px !important;
    height: 150px !important;
    max-width: 150px !important;
    border-radius: 50% !important;
    margin: 0 auto -70px auto !important;
    object-fit: cover;
    border: 3px solid #fff !important;
  }
  html[data-resume-format="resume6.html"] .r6-body {
    width: 100% !important;
    border-collapse: collapse !important;
    table-layout: fixed !important;
  }
  html[data-resume-format="resume6.html"] .r6-col-left,
  html[data-resume-format="resume6.html"] col.r6-col-left {
    width: 30% !important;
  }
  html[data-resume-format="resume6.html"] .r6-col-right,
  html[data-resume-format="resume6.html"] col.r6-col-right {
    width: 70% !important;
  }
  html[data-resume-format="resume6.html"] .r6-left,
  html[data-resume-format="resume6.html"] .r6-right {
    background-color: #a6a6a6 !important;
    vertical-align: top !important;
    -webkit-print-color-adjust: exact !important;
  }
  html[data-resume-format="resume6.html"] .r6-left {
    width: 30% !important;
  }
  html[data-resume-format="resume6.html"] .r6-right {
    width: 70% !important;
    border-left: 1px solid #5a5454 !important;
  }
  html[data-resume-format="resume6.html"] h2 {
    font-size: 22px !important;
    margin: 0 0 8px 0 !important;
  }
  html[data-resume-format="resume6.html"] p {
    font-size: 18px !important;
    word-break: normal !important;
    word-wrap: break-word !important;
  }
  html[data-resume-format="resume6.html"] .r6-sign,
  html[data-resume-format="resume6.html"] img[alt="Signature"] {
    width: 120px !important;
    height: 48px !important;
    max-width: 120px !important;
    max-height: 48px !important;
    object-fit: contain !important;
  }

  /* --- resume7.html: pink banners, contact icons, experience 30/70 --- */
  html[data-resume-format="resume7.html"],
  html[data-resume-format="resume7.html"] body {
    margin: 0 !important;
    padding: 10px 12px 14px 12px !important;
    background: #fff !important;
    font-size: 18px !important;
  }
  html[data-resume-format="resume7.html"] .r7-name {
    font-size: 32px !important;
    color: #ff3968 !important;
  }
  html[data-resume-format="resume7.html"] .r7-job,
  html[data-resume-format="resume7.html"] .r7-contact-text,
  html[data-resume-format="resume7.html"] .r7-h {
    color: #ff3968 !important;
  }
  html[data-resume-format="resume7.html"] .r7-icon {
    width: 22px !important;
    height: 22px !important;
    max-width: 22px !important;
    background-color: #ff3968 !important;
    color: #fff !important;
    border-radius: 50% !important;
    text-align: center !important;
    line-height: 22px !important;
    -webkit-print-color-adjust: exact !important;
  }
  html[data-resume-format="resume7.html"] .r7-icon svg {
    display: block !important;
    width: 12px !important;
    height: 12px !important;
    margin: 5px auto 0 auto !important;
  }
  html[data-resume-format="resume7.html"] .r7-pink {
    background-color: #ff3968 !important;
    color: #fff !important;
    -webkit-print-color-adjust: exact !important;
  }
  html[data-resume-format="resume7.html"] .r7-dates {
    width: 30% !important;
    color: #fff !important;
  }
  html[data-resume-format="resume7.html"] .r7-jobbody {
    width: 70% !important;
    color: #fff !important;
  }
  html[data-resume-format="resume7.html"] .r7-skill-box {
    width: 48% !important;
    float: left !important;
  }
  html[data-resume-format="resume7.html"] .r7-track {
    background-color: #e9ecef !important;
    height: 10px !important;
    overflow: hidden !important;
  }
  html[data-resume-format="resume7.html"] .r7-fill {
    background-color: #ff3968 !important;
    height: 10px !important;
    -webkit-print-color-adjust: exact !important;
  }
  html[data-resume-format="resume7.html"] .r7-sign,
  html[data-resume-format="resume7.html"] img[alt="Signature"] {
    width: 120px !important;
    height: 48px !important;
    max-width: 120px !important;
    max-height: 48px !important;
    object-fit: contain !important;
  }

  /* --- resume8.html: pink sidebar, skill bars, timeline --- */
  html[data-resume-format="resume8.html"],
  html[data-resume-format="resume8.html"] body {
    margin: 0 !important;
    padding: 0 !important;
    background: #fff !important;
    font-size: 18px !important;
  }
  html[data-resume-format="resume8.html"] .r8-page {
    width: 100% !important;
    height: 1120px !important;
    table-layout: fixed !important;
    border-collapse: collapse !important;
  }
  html[data-resume-format="resume8.html"] .r8-left {
    width: 32% !important;
    background-color: #e91e63 !important;
    color: #fff !important;
    vertical-align: top !important;
    -webkit-print-color-adjust: exact !important;
  }
  html[data-resume-format="resume8.html"] .r8-right {
    width: 68% !important;
    background: #fff !important;
    vertical-align: top !important;
  }
  html[data-resume-format="resume8.html"] .r8-photo {
    display: block !important;
    width: 100% !important;
    height: 220px !important;
    object-fit: cover;
  }
  html[data-resume-format="resume8.html"] .r8-sec-title {
    font-size: 22px !important;
    text-transform: uppercase !important;
  }
  html[data-resume-format="resume8.html"] .r8-right .r8-sec-title {
    color: #e91e63 !important;
  }
  html[data-resume-format="resume8.html"] .r8-track {
    height: 6px !important;
    background-color: #f48fb1 !important;
    overflow: hidden !important;
  }
  html[data-resume-format="resume8.html"] .r8-fill {
    height: 6px !important;
    background-color: #fff !important;
    -webkit-print-color-adjust: exact !important;
  }
  html[data-resume-format="resume8.html"] .r8-dot {
    width: 8px !important;
    height: 8px !important;
    border: 2px solid #e91e63 !important;
    border-radius: 50% !important;
    background: #fff !important;
  }
  html[data-resume-format="resume8.html"] .r8-line {
    width: 2px !important;
    height: 48px !important;
    background: #e91e63 !important;
    -webkit-print-color-adjust: exact !important;
  }
  html[data-resume-format="resume8.html"] .r8-sign,
  html[data-resume-format="resume8.html"] img[alt="sign"] {
    width: 120px !important;
    height: 48px !important;
    max-width: 120px !important;
    max-height: 48px !important;
    object-fit: contain !important;
  }

  /* --- resume9.html: navy header/footer, 2-col body --- */
  html[data-resume-format="resume9.html"],
  html[data-resume-format="resume9.html"] body {
    margin: 0 !important;
    padding: 0 !important;
    background: #fff !important;
    font-size: 18px !important;
  }
  html[data-resume-format="resume9.html"] .r9-head,
  html[data-resume-format="resume9.html"] .r9-foot {
    background-color: #1e3e5c !important;
    color: #fff !important;
    -webkit-print-color-adjust: exact !important;
  }
  html[data-resume-format="resume9.html"] .r9-head {
    border-bottom: 4px solid #02eeff !important;
  }
  html[data-resume-format="resume9.html"] .r9-foot {
    border-top: 4px solid #02eeff !important;
  }
  html[data-resume-format="resume9.html"] .r9-photo {
    width: 150px !important;
    height: 150px !important;
    border-radius: 50% !important;
    object-fit: cover;
  }
  html[data-resume-format="resume9.html"] .r9-name {
    font-size: 34px !important;
    color: #fff !important;
  }
  html[data-resume-format="resume9.html"] .r9-job {
    font-size: 22px !important;
    color: #fff !important;
  }
  html[data-resume-format="resume9.html"] .r9-line {
    background-color: #02eeff !important;
    height: 2px !important;
  }
  html[data-resume-format="resume9.html"] .r9-body {
    width: 100% !important;
    table-layout: fixed !important;
  }
  html[data-resume-format="resume9.html"] .r9-col {
    width: 50% !important;
    vertical-align: top !important;
  }
  html[data-resume-format="resume9.html"] .r9-h {
    color: #1e3e5c !important;
    font-size: 22px !important;
    font-style: italic !important;
  }
  html[data-resume-format="resume9.html"] .r9-hline {
    background: #111 !important;
    height: 2px !important;
  }
  html[data-resume-format="resume9.html"] .r9-arrow {
    width: 0 !important;
    height: 0 !important;
    border-top: 7px solid transparent !important;
    border-bottom: 7px solid transparent !important;
    border-left: 14px solid #02eeff !important;
  }
  html[data-resume-format="resume9.html"] .r9-harrow {
    width: 0 !important;
    height: 0 !important;
    border-top: 6px solid transparent !important;
    border-bottom: 6px solid transparent !important;
    border-left: 12px solid #111 !important;
  }
  html[data-resume-format="resume9.html"] .r9-sign,
  html[data-resume-format="resume9.html"] img[alt="Signature"] {
    width: 120px !important;
    height: 48px !important;
    max-width: 120px !important;
    max-height: 48px !important;
    object-fit: contain !important;
  }

  /* --- resume10.html: full-width beige header, photo overlap --- */
  html[data-resume-format="resume10.html"],
  html[data-resume-format="resume10.html"] body {
    margin: 0 !important;
    padding: 0 !important;
    background: #fff !important;
    font-size: 18px !important;
  }
  html[data-resume-format="resume10.html"] .r10-head,
  html[data-resume-format="resume10.html"] .r10-head-l,
  html[data-resume-format="resume10.html"] .r10-head-r {
    background-color: #cbb594 !important;
    -webkit-print-color-adjust: exact !important;
  }
  html[data-resume-format="resume10.html"] .r10-head {
    width: 100% !important;
    height: 168px !important;
    table-layout: fixed !important;
  }
  html[data-resume-format="resume10.html"] .r10-head-l {
    width: 35% !important;
  }
  html[data-resume-format="resume10.html"] .r10-head-r {
    width: 65% !important;
  }
  html[data-resume-format="resume10.html"] .r10-page {
    width: 100% !important;
    table-layout: fixed !important;
    border-collapse: collapse !important;
  }
  html[data-resume-format="resume10.html"] .r10-left {
    width: 35% !important;
    background-color: #cdcdcd !important;
    -webkit-print-color-adjust: exact !important;
    vertical-align: top !important;
  }
  html[data-resume-format="resume10.html"] .r10-right {
    width: 65% !important;
    background-color: #fff !important;
    vertical-align: top !important;
  }
  html[data-resume-format="resume10.html"] .r10-photo {
    width: 168px !important;
    height: 168px !important;
    border-radius: 50% !important;
    object-fit: cover;
  }
  html[data-resume-format="resume10.html"] .r10-name {
    font-size: 40px !important;
    color: #222 !important;
  }
  html[data-resume-format="resume10.html"] .r10-h {
    font-size: 22px !important;
    font-weight: bold !important;
    color: #111 !important;
  }
  html[data-resume-format="resume10.html"] .r10-dia {
    width: 7px !important;
    height: 7px !important;
    background-color: #111 !important;
    -webkit-print-color-adjust: exact !important;
  }
  html[data-resume-format="resume10.html"] .r10-sign,
  html[data-resume-format="resume10.html"] img[alt="Signature"] {
    width: 120px !important;
    height: 48px !important;
    max-width: 120px !important;
    max-height: 48px !important;
    object-fit: contain !important;
  }

  /* --- resume11.html: teal sidebar, peach name banner --- */
  html[data-resume-format="resume11.html"],
  html[data-resume-format="resume11.html"] body {
    margin: 0 !important;
    padding: 10px !important;
    background: #fff !important;
    font-size: 18px !important;
  }
  html[data-resume-format="resume11.html"] .r11-page {
    width: 100% !important;
    height: 1100px !important;
    table-layout: fixed !important;
    border-collapse: collapse !important;
  }
  html[data-resume-format="resume11.html"] .r11-left {
    width: 32% !important;
    background-color: #699897 !important;
    color: #fff !important;
    -webkit-print-color-adjust: exact !important;
    vertical-align: top !important;
  }
  html[data-resume-format="resume11.html"] .r11-right {
    width: 68% !important;
    background-color: #fff !important;
    vertical-align: top !important;
  }
  html[data-resume-format="resume11.html"] .r11-peach {
    background-color: #fdceb8 !important;
    -webkit-print-color-adjust: exact !important;
  }
  html[data-resume-format="resume11.html"] .r11-photo {
    width: 150px !important;
    height: 150px !important;
    border-radius: 50% !important;
    object-fit: cover;
  }
  html[data-resume-format="resume11.html"] .r11-name {
    font-size: 34px !important;
    color: #111 !important;
  }
  html[data-resume-format="resume11.html"] .r11-h {
    font-size: 22px !important;
    font-weight: bold !important;
  }
  html[data-resume-format="resume11.html"] .r11-tri {
    border-left: 10px solid #699897 !important;
    border-top: 6px solid transparent !important;
    border-bottom: 6px solid transparent !important;
  }
  html[data-resume-format="resume11.html"] .r11-lh,
  html[data-resume-format="resume11.html"] .r11-lval,
  html[data-resume-format="resume11.html"] .r11-litem {
    color: #fff !important;
  }
  html[data-resume-format="resume11.html"] .r11-sign,
  html[data-resume-format="resume11.html"] img[alt="Signature"] {
    width: 120px !important;
    height: 48px !important;
    max-width: 120px !important;
    max-height: 48px !important;
    object-fit: contain !important;
  }

  /* --- resume12.html: light-blue main, navy profile card --- */
  html[data-resume-format="resume12.html"],
  html[data-resume-format="resume12.html"] body {
    margin: 0 !important;
    padding: 8px !important;
    background: #fff !important;
    font-size: 18px !important;
  }
  html[data-resume-format="resume12.html"] .r12-page,
  html[data-resume-format="resume12.html"] .r12-main,
  html[data-resume-format="resume12.html"] .r12-side,
  html[data-resume-format="resume12.html"] .r12-rail {
    background-color: #bad4e4 !important;
    -webkit-print-color-adjust: exact !important;
  }
  html[data-resume-format="resume12.html"] .r12-page {
    width: 100% !important;
    height: 1100px !important;
    table-layout: fixed !important;
  }
  html[data-resume-format="resume12.html"] .r12-main { width: 62% !important; vertical-align: top !important; }
  html[data-resume-format="resume12.html"] .r12-side { width: 34% !important; vertical-align: top !important; }
  html[data-resume-format="resume12.html"] .r12-rail { width: 4% !important; }
  html[data-resume-format="resume12.html"] .r12-accent,
  html[data-resume-format="resume12.html"] .r12-rail-top {
    background-color: #2d3f65 !important;
    -webkit-print-color-adjust: exact !important;
  }
  html[data-resume-format="resume12.html"] .r12-namecard {
    background-color: #fff !important;
  }
  html[data-resume-format="resume12.html"] .r12-card {
    background-color: #101f38 !important;
    color: #fff !important;
    -webkit-print-color-adjust: exact !important;
  }
  html[data-resume-format="resume12.html"] .r12-photo {
    width: 150px !important;
    height: 150px !important;
    border-radius: 50% !important;
    object-fit: cover;
  }
  html[data-resume-format="resume12.html"] .r12-name {
    font-size: 34px !important;
    color: #111 !important;
  }
  html[data-resume-format="resume12.html"] .r12-h {
    font-size: 22px !important;
    font-weight: bold !important;
  }
  html[data-resume-format="resume12.html"] .r12-sh,
  html[data-resume-format="resume12.html"] .r12-sval,
  html[data-resume-format="resume12.html"] .r12-skill-name,
  html[data-resume-format="resume12.html"] .r12-wval {
    color: #fff !important;
  }
  html[data-resume-format="resume12.html"] .r12-track {
    background-color: #8b93a3 !important;
    height: 10px !important;
    -webkit-print-color-adjust: exact !important;
  }
  html[data-resume-format="resume12.html"] .r12-fill {
    background-color: #fff !important;
    height: 10px !important;
    -webkit-print-color-adjust: exact !important;
  }
  html[data-resume-format="resume12.html"] .r12-pct {
    color: #fff !important;
    font-size: 18px !important;
    font-weight: bold !important;
    width: 46px !important;
    text-align: right !important;
  }
  html[data-resume-format="resume12.html"] .r12-sign,
  html[data-resume-format="resume12.html"] img[alt="Signature"] {
    width: 120px !important;
    height: 48px !important;
    max-width: 120px !important;
    max-height: 48px !important;
    object-fit: contain !important;
  }

  html[data-resume-format="resume3.html"] img[alt="sign"],
  html[data-resume-format="resume3.html"] .signature-img {
    display: block !important;
    width: 120px !important;
    height: 48px !important;
    max-width: 120px !important;
    max-height: 48px !important;
    margin: 8px 0 0 24px !important;
    object-fit: contain !important;
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
