<?php

namespace App\Http\Middleware;

use App\Services\ResumeTemplateDataMapper;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Proves Postman/browser hit Laravel (not Node port 3000). Check response headers.
 */
class ResumeApiIdentity
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        return $response
            ->header('X-Resume-Engine', 'laravel')
            ->header('X-Mapper-Version', ResumeTemplateDataMapper::VERSION);
    }
}
