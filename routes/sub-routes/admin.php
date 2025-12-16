<?php


use App\Helpers\Roles;
use Illuminate\Support\Facades\Route;



Route::group(['prefix' => 'v1/admin', 'middleware' => ['auth:api']], function () {
    Route::prefix('users')->group(function () {
        Route::get('/', [App\Http\Controllers\Api\v1\UserController::class, 'adminIndex']);
        Route::post('/', [App\Http\Controllers\Api\v1\UserController::class, 'store']);
        Route::put('/{user}', [App\Http\Controllers\Api\v1\UserController::class, 'update'])->whereNumber('user');
        Route::get('/{user}', [App\Http\Controllers\Api\v1\UserController::class, 'show'])->whereNumber('user');
        Route::delete('/{user}', [App\Http\Controllers\Api\v1\UserController::class, 'destroy'])->whereNumber('user');
    });
});
