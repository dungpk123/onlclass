<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\ClassRoomController;
use App\Http\Controllers\Api\EvaluationController;
use App\Http\Controllers\Api\DocumentController;
use App\Http\Controllers\Api\DashboardController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Auth routes
Route::group(['prefix' => 'auth'], function () {
    Route::post('login', [AuthController::class, 'login']);
    Route::post('register', [AuthController::class, 'register']);
    Route::post('logout', [AuthController::class, 'logout']);
    Route::post('refresh', [AuthController::class, 'refresh']);
    Route::get('profile', [AuthController::class, 'userProfile']);
});

// Protected routes
Route::middleware('auth:api')->group(function () {
    // Dashboard
    Route::get('dashboard', [DashboardController::class, 'index']);

    // User management routes (Admin only)
    Route::middleware('admin')->group(function () {
        Route::apiResource('users', UserController::class);
        Route::get('teachers', [UserController::class, 'teachers']);
        Route::get('students', [UserController::class, 'students']);
    });


    // Profile routes (for current user)
    Route::get('profile', [UserController::class, 'profile']);
    Route::put('profile', [UserController::class, 'updateProfile']);

    // Class management routes
    Route::apiResource('classes', ClassRoomController::class);

    // Student self-enrollment routes
    Route::post('classes/{id}/join', [ClassRoomController::class, 'enrollSelf']);
    Route::delete('classes/{id}/leave', [ClassRoomController::class, 'leaveSelf']);

    // Admin/Teacher enrollment management
    Route::post('classes/{id}/enroll', [ClassRoomController::class, 'enrollStudent']);
    Route::delete('classes/{id}/students', [ClassRoomController::class, 'removeStudent']);

    // Evaluation routes
    Route::apiResource('evaluations', EvaluationController::class);
    Route::get('teachers/{id}/evaluations', [EvaluationController::class, 'teacherSummary']);
    Route::get('classes/{id}/my-evaluation', [EvaluationController::class, 'myEvaluation']);

    // Document routes
    Route::apiResource('documents', DocumentController::class);
    Route::get('documents/{id}/download', [DocumentController::class, 'download']);
});
