<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CampaignController;
use App\Http\Controllers\TelnyxController;
use App\Models\Campaign;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::controller(AuthController::class)->prefix('auth')->group(function () {
    Route::post('login', 'login');
});

Route::controller(CampaignController::class)->prefix('campaigns')->group(function () {
    Route::post('/', [CampaignController::class, 'store']);
    Route::get('/', [CampaignController::class, 'index']);
    Route::get('/{campaign}', [CampaignController::class, 'show']);
    Route::put('/{campaign}', [CampaignController::class, 'update']);
    Route::delete('/{campaign}', [CampaignController::class, 'destroy']);
});

Route::prefix('webhooks')->group(function () {
    Route::post('/telnyx', [TelnyxController::class, 'webhook']);
});
