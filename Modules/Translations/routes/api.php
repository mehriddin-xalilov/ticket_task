<?php

use App\Helpers\Roles;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Modules\Translations\App\Http\Controllers\TranslationsController;

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



Route::prefix('v1')->group(function () {
    Route::group(['prefix' => 'admin/translations', 'middleware' => ['auth:api', 'scope:' . Roles::ROLE_ADMIN]], function () {
        Route::get('/', [TranslationsController::class, 'adminIndex']);
        Route::post('/', [TranslationsController::class, 'store']);
        Route::put('/{message}',  [TranslationsController::class, 'update'])->whereNumber('message');
        Route::get('/{message}',  [TranslationsController::class, 'show'])->whereNumber('message');
        Route::get('/list',  [TranslationsController::class, 'list']);
    });
});
