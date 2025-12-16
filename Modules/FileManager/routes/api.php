<?php

use App\Helpers\Roles;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Modules\FileManager\App\Http\Controllers\FileManagerController;

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

Route::prefix('v1/files')->middleware('auth:api')->group(function () {
    Route::post('/upload', [FileManagerController::class, 'upload']);
    Route::delete('{file}', [FileManagerController::class, 'delete'])->whereNumber('file');
    Route::get('/{file}', [FileManagerController::class, 'show'])->whereNumber('file');
    Route::get('/{key}', [FileManagerController::class, 'showByKey']);
    Route::post('/check-upload', [FileManagerController::class, 'checkUpload']);
});
