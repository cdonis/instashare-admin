<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\FilesController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::group(['prefix' => 'admin', 'middleware' => ['formatResponse']], function() {
    Route::group(['prefix' => 'auth'], function () {
        Route::post     ('signup',              [AuthController::class, 'signup']);
        Route::post     ('login',               [AuthController::class, 'login']);
        Route::post     ('logout',              [AuthController::class, 'logout'])->middleware('auth:sanctum');
    });

    Route::group(['middleware' => ['auth:sanctum']], function () {
        Route::get      ('files',               [FilesController::class, 'index']);
        Route::get      ('files/{id}',          [FilesController::class, 'show']);
        Route::get      ('files/{id}/download', [FilesController::class, 'download']);
        Route::post     ('files',               [FilesController::class, 'store']);
        Route::match    (['put', 'patch'], 'files/{id}', [FilesController::class, 'update']);
        Route::delete   ('files/{id}',          [FilesController::class, 'destroy']);
    });
});