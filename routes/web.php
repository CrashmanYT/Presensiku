<?php

use App\Livewire\RealtimeAttendanceDashboard;
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

require __DIR__.'/auth.php';
