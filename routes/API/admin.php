<?php

use App\Http\Controllers\API\Admin\AuthController;
use App\Http\Controllers\API\Admin\LocationController;
use App\Http\Controllers\API\Admin\MailController;
use App\Http\Controllers\API\Admin\OAuthController;
use App\Http\Controllers\API\Admin\PermissionController;
use App\Http\Controllers\API\Admin\RoleController;
use App\Http\Controllers\API\Admin\UserController;
use App\Http\Controllers\API\Admin\OtpController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'admin/v1/', 'namespace' => 'App\Http\Controllers\API\Admin'], function () {

    Route::post('login', [AuthController::class, 'login'])->name('login');

    Route::middleware(['auth:sanctum'])->group(function () {
        Route::middleware(['admin'])->group(function () {

            Route::get('logout', [AuthController::class, 'logout'])->name('logout');

            // Users (admin)
            Route::get('users', [UserController::class, 'index']);
            Route::post('users', [UserController::class, 'store']);
            Route::get('users/{id}', [UserController::class, 'show']);
            Route::put('users/{id}', [UserController::class, 'update']);
            Route::delete('users/{id}', [UserController::class, 'destroy']);
            Route::get('users/change_password/{id}', [UserController::class, 'changePassword']);

            Route::put('users/unarchive/{id}', [UserController::class, 'unArchive']);
            Route::delete('users/permanent-delete/{id}', [UserController::class, 'permanentDestroy']);

            // Role
            Route::get('roles', [RoleController::class, 'index']);
            Route::post('roles', [RoleController::class, 'store']);
            Route::get('roles/{id}', [RoleController::class, 'show']);
            Route::put('roles/{id}', [RoleController::class, 'update']);
            Route::delete('roles/{id}', [RoleController::class, 'destroy']);

            // Permissions
            Route::get('permissions', [PermissionController::class, 'index']);
            Route::post('permissions', [PermissionController::class, 'store']);
            Route::get('permissions/{id}', [PermissionController::class, 'show']);
            Route::put('permissions/{id}', [PermissionController::class, 'update']);
            Route::delete('permissions/{id}', [PermissionController::class, 'destroy']);

            //location
            Route::apiResource('locations', LocationController::class);
            Route::put('locations/unarchive/{id}', [LocationController::class, 'unArchive']);
            Route::delete('locations/permanent-delete/{id}', [LocationController::class, 'permanentDestroy']);
        });
    });

    Route::post('/get-token', [OAuthController::class, 'doGenerateToken']);
    Route::get('/gettoken', [OAuthController::class, 'doSuccessToken']);

    Route::post('/otp', [OtpController::class, 'requestOTP']);
    Route::post('/otp_verify', [OtpController::class, 'verifyOtp']);

    Route::post('/feedback', [MailController::class, 'userSendMail'])->name('feedback');
});
