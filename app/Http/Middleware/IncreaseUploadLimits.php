<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IncreaseUploadLimits
{
    public function handle(Request $request, Closure $next): Response
    {
        // Match Node body-parser 2000mb limit as closely as PHP allows
        @ini_set('post_max_size', '2000M');
        @ini_set('upload_max_filesize', '2000M');
        @ini_set('memory_limit', '2048M');
        @ini_set('max_execution_time', '300');

        return $next($request);
    }
}
