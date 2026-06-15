<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AuditLogController;
use App\Http\Controllers\Api\BookController;
use App\Http\Controllers\Api\CourseController;
use App\Http\Controllers\Api\DeviceController;
use App\Http\Controllers\Api\ExamController;
use App\Http\Controllers\Api\LessonController;
use App\Http\Controllers\Api\LessonFileController;
use App\Http\Controllers\Api\OrganizationController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\PaymentReceiptController;
use App\Http\Controllers\Api\QuestionController;
use App\Http\Controllers\Api\RegistrationCodeController;
use App\Http\Controllers\Api\StudentRegistrationController;
use App\Http\Controllers\Api\StudentController;
use App\Http\Controllers\Api\TeacherController;
use App\Http\Controllers\Api\VideoSecurityController;
use App\Http\Controllers\Api\WalletController;
use App\Http\Controllers\Api\WithdrawRequestController;
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
    Route::post('/device/check', [DeviceController::class, 'check']);

    Route::middleware(['role:super_admin'])->prefix('admin')->group(function () {
        Route::apiResource('organizations', OrganizationController::class);
    });

    Route::middleware(['permission:manage_students|manage_organizations'])->group(function () {
        Route::post('/codes/generate', [RegistrationCodeController::class, 'generate']);
        Route::get('/codes/{registrationCode}/qr', [RegistrationCodeController::class, 'qr']);
    });

    Route::middleware('tenant')->group(function () {
        Route::apiResource('courses', CourseController::class);
        Route::apiResource('teachers', TeacherController::class)->middleware('permission:manage_teachers');
        Route::apiResource('students', StudentController::class)->except(['destroy'])->middleware('permission:manage_students');

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
        Route::get('/videos/{video}/signed-url', [VideoSecurityController::class, 'signedUrl']);

        Route::get('/books', [BookController::class, 'index']);
        Route::post('/books', [BookController::class, 'store'])->middleware('permission:manage_courses');
        Route::post('/books/{book}/purchase', [BookController::class, 'purchase']);

        Route::post('/payment-receipts', [PaymentReceiptController::class, 'store']);
        Route::put('/payment-receipts/{paymentReceipt}', [PaymentReceiptController::class, 'update'])->middleware('permission:manage_payments');

        Route::get('/payments', [PaymentController::class, 'index'])->middleware('permission:manage_payments|view_reports');
        Route::post('/payments', [PaymentController::class, 'store'])->middleware('permission:manage_payments');
        Route::put('/payments/{payment}', [PaymentController::class, 'update'])->middleware('permission:manage_payments');

        Route::get('/wallets', [WalletController::class, 'index'])->middleware('permission:manage_wallets|view_reports');
        Route::get('/wallets/{wallet}', [WalletController::class, 'show'])->middleware('permission:manage_wallets|view_reports');

        Route::get('/withdraw-requests', [WithdrawRequestController::class, 'index'])->middleware('permission:manage_wallets|view_reports');
        Route::post('/withdraw-requests', [WithdrawRequestController::class, 'store'])->middleware('permission:manage_wallets');
        Route::put('/withdraw-requests/{withdrawRequest}', [WithdrawRequestController::class, 'update'])->middleware('permission:manage_wallets');

        Route::get('/audit-logs', [AuditLogController::class, 'index'])->middleware('permission:view_reports');
    });
});
