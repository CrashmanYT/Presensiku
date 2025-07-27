<?php

use App\Livewire\RealtimeAttendanceDashboard;
use App\Http\Controllers\TemplateController;
use Illuminate\Support\Facades\Route;

// Redirect root to attendance dashboard
Route::redirect('/', '/attendance-dashboard')->name('home');

// Realtime Attendance Dashboard (Public access)
Route::get('/attendance-dashboard', RealtimeAttendanceDashboard::class)->name('attendance.dashboard');

// Test route for triggering dashboard updates
Route::get('/test-scan/{fingerprintId}', function ($fingerprintId) {
    broadcast(new \App\Events\UserScanned($fingerprintId));
    return response()->json(['message' => 'Scan event triggered', 'fingerprint_id' => $fingerprintId]);
})->name('test.scan');

// Template download routes
Route::middleware('auth')->group(function () {
    Route::get('/template/student', [TemplateController::class, 'downloadStudentTemplate'])->name('template.student');
    Route::get('/template/teacher', [TemplateController::class, 'downloadTeacherTemplate'])->name('template.teacher');
    Route::get('/template/class', [TemplateController::class, 'downloadClassTemplate'])->name('template.class');
});

require __DIR__.'/auth.php';
