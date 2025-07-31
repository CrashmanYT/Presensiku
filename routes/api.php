<?php

use App\Http\Controllers\Api\WebhookController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Webhook routes for fingerprint devices
Route::post('/webhook/fingerprint', [WebhookController::class, 'handleAttendance']);
//Route::post('/webhook/test-scan', [WebhookController::class, 'testScan']);

// Fingerspot webhook (active route)
Route::post('/webhook/fingerspot', [WebhookController::class, 'handleAttendance']);

Route::post('/webhook/student-leave-request', [WebhookController::class, 'handleStudentLeaveRequest']);
Route::post('/webhook/teacher-leave-request', [WebhookController::class, 'handleTeacherLeaveRequest']);
