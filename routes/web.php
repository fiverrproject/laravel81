<?php

use App\Http\Controllers\ChatGptController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ResumeBuilderController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index']);

Route::any('/get/chatgpt-data', [ChatGptController::class, 'getGptResult']);
Route::any('/resume/create', [ResumeBuilderController::class, 'createResume']);
Route::get('/resume/mapper-check', [ResumeBuilderController::class, 'mapperCheck']);
Route::any('/resume/createweb', [ResumeBuilderController::class, 'createResumeForWeb']);
Route::any('/cv/create', [ResumeBuilderController::class, 'createCV']);
Route::any('/test-api', [ResumeBuilderController::class, 'testApi']);
Route::any('/festival/create', [ResumeBuilderController::class, 'festivalPost']);
Route::any('/statusMaker/create', [ResumeBuilderController::class, 'statusMaker']);
Route::any('/invitationCard/create', [ResumeBuilderController::class, 'invitationCard']);
Route::any('/instagram', [ResumeBuilderController::class, 'instagram']);
Route::any('/versioncheck/index.php', [ResumeBuilderController::class, 'versionCheck']);

// Angular SPA fallback — exclude /api/* so API POSTs are not blocked
Route::get('/{any}', function () {
    $index = public_path('frontend/index.html');
    if (is_file($index)) {
        return response()->file($index);
    }

    return response('Frontend not found', 404);
})->where('any', '^(?!api(?:/|$)).*');
