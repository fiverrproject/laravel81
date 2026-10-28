<?php

use App\Http\Controllers\ChatGptController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ResumeBuilderController;
use Illuminate\Support\Facades\Route;

/*
| API routes (prefixed with /api automatically)
| Example: POST http://127.0.0.1:8002/api/resume/create
*/

Route::get('/', [HomeController::class, 'index']);

Route::any('/get/chatgpt-data', [ChatGptController::class, 'getGptResult']);
Route::middleware('resume.api.identity')->group(function () {
    Route::any('/resume/create', [ResumeBuilderController::class, 'createResume']);
    Route::get('/resume/mapper-check', [ResumeBuilderController::class, 'mapperCheck']);
    Route::get('/resume/deploy-fingerprint', [ResumeBuilderController::class, 'deployFingerprint']);
    Route::any('/resume/createweb', [ResumeBuilderController::class, 'createResumeForWeb']);
    Route::any('/cv/create', [ResumeBuilderController::class, 'createCV']);
    Route::any('/test-api', [ResumeBuilderController::class, 'testApi']);
    Route::any('/festival/create', [ResumeBuilderController::class, 'festivalPost']);
    Route::any('/statusMaker/create', [ResumeBuilderController::class, 'statusMaker']);
    Route::any('/invitationCard/create', [ResumeBuilderController::class, 'invitationCard']);
    Route::any('/instagram', [ResumeBuilderController::class, 'instagram']);
    Route::any('/versioncheck/index.php', [ResumeBuilderController::class, 'versionCheck']);
});
