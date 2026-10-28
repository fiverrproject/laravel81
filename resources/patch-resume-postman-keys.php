<?php
/**
 * Safe patches — ONLY laravel81/resources/resume-builder/resume*.html
 * cd laravel81 && php resources/patch-resume-postman-keys.php
 */

$dir = __DIR__.'/resume-builder';
$files = glob($dir.'/resume*.html') ?: [];
if ($files === []) {
    exit("No templates in {$dir}\n");
}

$skillFigcaption = <<<'OLD'
                           {{#if skillName}}
                           <figcaption class="diagram mb-8">
                              <h5 class="diagram--id" style="word-break: break-all;">{{skillName}}</h5> <span
                                 class="skill-bar" style="width:{{expertise}}%;">{{expertise}}%</span>
                           </figcaption>
                           {{/if}}
OLD;

$skillFigcaptionNew = <<<'NEW'
                           {{#if skillName}}
                           <figcaption class="diagram mb-8">
                              <h5 class="diagram--id" style="word-break: break-all;">{{skillName}}</h5> <span
                                 class="skill-bar" style="width:{{expertise}}%;">{{expertise}}%</span>
                           </figcaption>
                           {{else}}{{#if name}}
                           <figcaption class="diagram mb-8">
                              <h5 class="diagram--id" style="word-break: break-all;">{{name}}</h5> <span
                                 class="skill-bar" style="width:{{#if expertise}}{{expertise}}{{else}}0{{/if}}%;">{{#if expertise}}{{expertise}}{{else}}0{{/if}}%</span>
                           </figcaption>
                           {{/if}}{{/if}}
NEW;

$interestRow = <<<'OLD'
                           {{#if hoby}}
                           <li class="hoby pl-20 showPosts">
                              <span class="greekCross">&gt;</span>
                              <p class="text break-all"> {{hoby}}</p>
                           </li>
                           {{/if}}
OLD;

$interestRowNew = <<<'NEW'
                           <li class="hoby pl-20 showPosts">
                              <span class="greekCross">&gt;</span>
                              <p class="text break-all"> {{#if hoby}}{{hoby}}{{else}}{{this}}{{/if}}</p>
                           </li>
NEW;

$langRow = <<<'OLD'
                           {{#if name}}
                           <li class="hoby pl-20 showPosts">
                              <span class="greekCross">&gt;</span>
                              <p class="text break-all"> {{name}}</p>
                           </li>
                           {{/if}}
OLD;

$langRowNew = <<<'NEW'
                           <li class="hoby pl-20 showPosts">
                              <span class="greekCross">&gt;</span>
                              <p class="text break-all"> {{#if name}}{{name}}{{else}}{{language}}{{#if level}} ({{level}}){{/if}}{{/if}}</p>
                           </li>
NEW;

$n = 0;
foreach ($files as $file) {
    $c = file_get_contents($file);
    // Allow re-run for education/project guard fixes
    $c = str_replace(' data-postman-fallbacks="1"', '', $c);
    $o = $c;
    $c = str_replace($skillFigcaption, $skillFigcaptionNew, $c);
    $c = str_replace($interestRow, $interestRowNew, $c);
    $c = str_replace($langRow, $langRowNew, $c);
    $c = str_replace('{{#if course}}<span', '{{#if course}}<span', $c);
    $c = preg_replace(
        '/\{\{#if course\}\}(<span[^>]*>)\s*\{\{course\}\}/',
        '{{#if course}}$1{{#if course}}{{course}}{{else}}{{degree}}{{/if}}',
        $c
    );
    $c = str_replace(' {{course}} ', ' {{#if course}}{{course}}{{else}}{{degree}}{{/if}} ', $c);

    // Education: outer {{#if course}} hides when JSON only has "degree"
    $eduSpan = '{{#if course}}<span class="text sub--text pl-20"
                              style=" font-size: var(--fs--01); text-transform: uppercase; font-weight: 600; color: var(--text-clr-200);">{{#if course}}{{course}}{{else}}{{degree}}{{/if}}</span>{{/if}}';
    $eduSpanNew = '{{#if course}}<span class="text sub--text pl-20"
                              style=" font-size: var(--fs--01); text-transform: uppercase; font-weight: 600; color: var(--text-clr-200);">{{course}}</span>{{else}}{{#if degree}}<span class="text sub--text pl-20"
                              style=" font-size: var(--fs--01); text-transform: uppercase; font-weight: 600; color: var(--text-clr-200);">{{degree}}</span>{{/if}}{{/if}}';
    $c = str_replace($eduSpan, $eduSpanNew, $c);

    $instBlock = <<<'OLD'
                              {{#if institute}}
                              <li class="hoby pl-20 showPosts">
                                 <span class="greekCross">&gt;</span>
                                 <p class="text break-all"> {{#if institute}}{{institute}}{{else}}{{institution}}{{/if}}</p>
                              </li>
                              {{/if}}
OLD;
    $instBlockNew = <<<'NEW'
                              <li class="hoby pl-20 showPosts">
                                 <span class="greekCross">&gt;</span>
                                 <p class="text break-all"> {{#if institute}}{{institute}}{{else}}{{institution}}{{/if}}</p>
                              </li>
NEW;
    $c = str_replace($instBlock, $instBlockNew, $c);

    $projSpan = '{{#if projectTitle}}<span class="text sub--text pl-20"
                              style=" font-size: var(--fs--01); text-transform: uppercase; font-weight: 600; color: var(--text-clr-200);">{{#if projectTitle}}{{projectTitle}}{{else}}{{title}}{{/if}}</span>{{/if}}';
    $projSpanNew = '{{#if projectTitle}}<span class="text sub--text pl-20"
                              style=" font-size: var(--fs--01); text-transform: uppercase; font-weight: 600; color: var(--text-clr-200);">{{projectTitle}}</span>{{else}}{{#if title}}<span class="text sub--text pl-20"
                              style=" font-size: var(--fs--01); text-transform: uppercase; font-weight: 600; color: var(--text-clr-200);">{{title}}</span>{{/if}}{{/if}}';
    $c = str_replace($projSpan, $projSpanNew, $c);

    $c = str_replace('{{institute}}', '{{#if institute}}{{institute}}{{else}}{{institution}}{{/if}}', $c);
    $c = str_replace('{{projectTitle}}', '{{#if projectTitle}}{{projectTitle}}{{else}}{{title}}{{/if}}', $c);
    $c = str_replace('{{projectDescription}}', '{{#if projectDescription}}{{projectDescription}}{{else}}{{description}}{{/if}}', $c);
    $c = str_replace('{{roles}}', '{{#if roles}}{{roles}}{{else}}{{role}}{{/if}}', $c);
    $c = str_replace('{{jobTitle}}', '{{#if jobTitle}}{{jobTitle}}{{else}}{{designation}}{{/if}}', $c);
    $c = str_replace('{{organization}}', '{{#if organization}}{{organization}}{{else}}{{company}}{{/if}}', $c);
    $c = str_replace('{{achivement}}', '{{#if achivement}}{{achivement}}{{else}}{{#if title}}{{title}}{{#if description}} - {{description}}{{/if}}{{/if}}{{/if}}', $c);
    $c = preg_replace('/<html/i', '<html data-postman-fallbacks="1"', $c, 1);
    if ($c !== $o) {
        file_put_contents($file, $c);
        $n++;
        echo basename($file)."\n";
    }
}
echo "Patched {$n} files. Upload laravel81/resources/resume-builder/ to server only.\n";
