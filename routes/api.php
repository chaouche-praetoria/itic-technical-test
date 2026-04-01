<?php

use App\Http\Controllers\Api\TestController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    Route::post('/candidates', [TestController::class, 'createCandidate']);
    Route::post('/generate-link', [TestController::class, 'generateLink']);
    Route::get('/results/{session}', [TestController::class, 'getResults']);
});
