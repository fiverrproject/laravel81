<?php

/**
 * Pure Laravel health check — works when document root is THIS public/ folder.
 * Open: https://resumesmaker.com/laravel-resume-check.php
 * If you see JSON with resume_engine=laravel, Laravel is live (not Node).
 */
declare(strict_types=1);

require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';

/** @var Illuminate\Contracts\Http\Kernel $kernel */
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$request = Illuminate\Http\Request::create('/resume/deploy-fingerprint', 'GET');
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
