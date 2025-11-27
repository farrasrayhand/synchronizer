<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DapodikController;

Route::group(['prefix' => 'auth'], function () {
    Route::post('login/{provider}', [AuthController::class, 'login'])->name('login');
});
Route::group(['middleware' => 'auth:sanctum'], function () {
    Route::get('sekolah', [DapodikController::class, 'index']);
    Route::post('sekolah', [DapodikController::class, 'sekolah']);
    Route::post('kirim-data', [DapodikController::class, 'kirim_data']);
    Route::get('reset', [DapodikController::class, 'reset']);
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
});
