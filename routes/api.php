<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CourseController;
use App\Http\Controllers\Api\ExamController;
use App\Http\Controllers\Api\LessonController;
use App\Http\Controllers\Api\LessonFileController;
use App\Http\Controllers\Api\OrganizationController;
use App\Http\Controllers\Api\QuestionController;
use App\Http\Controllers\Api\RegistrationCodeController;
use App\Http\Controllers\Api\StudentRegistrationController;
use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'app' => config('app.name'),
    ]);
});

Route::post('/login', [AuthController::class, 'login']);
Route::post('/student/register', [StudentRegistrationController::class, 'register']);
Route::post('/codes/validate', [RegistrationCodeController::class, 'validateCode']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    Route::middleware(['role:super_admin'])->prefix('admin')->group(function () {
        Route::apiResource('organizations', OrganizationController::class);
    });

    Route::middleware(['permission:manage_students|manage_organizations'])->group(function () {
        Route::post('/codes/generate', [RegistrationCodeController::class, 'generate']);
        Route::get('/codes/{registrationCode}/qr', [RegistrationCodeController::class, 'qr']);
    });

    Route::middleware('tenant')->group(function () {
        Route::apiResource('courses', CourseController::class);
        Route::post('/lessons', [LessonController::class, 'store']);
        Route::put('/lessons/{lesson}', [LessonController::class, 'update']);
        Route::delete('/lessons/{lesson}', [LessonController::class, 'destroy']);

        Route::post('/exams', [ExamController::class, 'store']);
        Route::get('/exams/{exam}', [ExamController::class, 'show']);
        Route::put('/exams/{exam}', [ExamController::class, 'update']);
        Route::delete('/exams/{exam}', [ExamController::class, 'destroy']);

        Route::post('/questions', [QuestionController::class, 'store']);
        Route::put('/questions/{question}', [QuestionController::class, 'update']);
        Route::delete('/questions/{question}', [QuestionController::class, 'destroy']);

        Route::get('/lesson-files/{lessonFile}/download', [LessonFileController::class, 'download']);
    });
});
