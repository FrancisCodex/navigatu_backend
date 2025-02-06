<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\MentorController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\LoginController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Routes that can be accessed by Leader
Route::middleware(['auth:sanctum', 'leader'])->group(function () {
    Route::get('appointments', [AppointmentController::class, 'show']);
    Route::post('appointments', [AppointmentController::class, 'store']);
    Route::delete('appointments/{id}', [AppointmentController::class, 'destroy']);
});

// Routes that can be accessed by Admin and Leader
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('mentors', [MentorController::class, 'index']);
    Route::get('mentors/{id}', [MentorController::class, 'show']);
});

// Mentors Routes
// Mentor Routes for Admin
Route::middleware(['auth:sanctum', 'admin'])->group(function () {
    Route::get('mentors', [MentorController::class, 'index']);
    Route::post('mentors', [MentorController::class, 'store']);
    Route::get('mentors/{id}', [MentorController::class, 'show']);
    // All Appointments
    Route::get('appointments', [AppointmentController::class, 'index']);
});


// Auth Routes Login and Register

Route::post('register', [RegisterController::class, 'register']);
Route::post('login', [LoginController::class, 'login']);
