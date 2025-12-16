<?php

use App\Http\Controllers\Api\v1\AuthController;
use App\Http\Controllers\Api\v1\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('/', function (Request $request) {
        return okResponse(['version' => '1.0.0']);
    });
//    Route::group(['prefix' => 'auth',], function () {
//        Route::post('login', [AuthController::class, 'login']);
//        Route::post('social-login', [AuthController::class, "socialLogin"]);
//    });
    Route::group(['prefix' => 'auth/admin',], function () {
        Route::post('login', [AuthController::class, 'adminLogin']);
    });
});

Route::prefix('v1')->group(function () {
    Route::middleware('auth:api')->group(function () {

        Route::prefix('users')->group(function () {
            Route::get('/', [UserController::class, 'index']);
            Route::get('/get-me', [App\Http\Controllers\Api\v1\AuthController::class, 'getMe']);
            Route::put('/update-me', [App\Http\Controllers\Api\v1\AuthController::class, 'updateMe']);
            Route::post('/logout', [App\Http\Controllers\Api\v1\AuthController::class, 'logout']);

        });
    });
});
//-------------- Admin start ------------------//
require __DIR__.'/sub-routes/admin.php';
//-------------- Admin end ------------------//


//-------------- Clint start ------------------//

require __DIR__.'/sub-routes/web.php';

//-------------- Clint end ------------------//



