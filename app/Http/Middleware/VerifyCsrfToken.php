<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        'api/*',
        'get/chatgpt-data',
        'resume/create',
        'resume/createweb',
        'cv/create',
        'test-api',
        'festival/create',
        'statusMaker/create',
        'invitationCard/create',
        'instagram',
        'versioncheck/index.php',
    ];
}
