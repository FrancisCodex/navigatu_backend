<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\MentorController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\SubmissionController;
use App\Http\Controllers\ActivityController;
use App\Http\Controllers\Incubatees\IncubateesController;
use App\Http\Controllers\Incubatees\StartupProfileController;
use App\Http\Controllers\Incubatees\MemberController;
use App\Http\Controllers\Incubatees\DocumentController;
use App\Http\Controllers\Incubatees\AchievementController;
use App\Http\Controllers\Dashboard\LeaderController;
use App\Http\Controllers\Dashboard\DashboardDataController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Routes that can be accessed by incubatee
Route::middleware(['auth:sanctum', 'leader'])->group(function () {
    Route::post('appointments', [AppointmentController::class, 'store']);
    Route::put('appointments/cancel/{id}', [AppointmentController::class, 'cancelAppointment']);
    Route::get('submissions/check/{activity_id}', [SubmissionController::class, 'checkSubmission']);


    // Activity Related Routes
    Route::post('activities/submit/{id}', [SubmissionController::class, 'store']);

    // Add members to startup
    Route::post('startup/add/member/{startup_profile_id}', [MemberController::class, 'store']);

    //Startup Routes
    Route::get('startup/myteam', [StartupProfileController::class, 'myStartupProfile']);
    //Document Routes
    Route::post('documents/upload/{startup_profile_id}', [DocumentController::class, 'upload']);
    Route::delete('documents/delete/{documentId}', [DocumentController::class, 'destroy']);

    //Data Routes
    Route::get('data/dashboard/incubatee', [DashboardDataController::class, 'getIncubateeDashboardDetails']);
    Route::get('data/myteam', [DashboardDataController::class, 'getIncubateeStartupProfile']);
});

// Routes that can be accessed by Admin and incubatee
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('appointments/{id}', [AppointmentController::class, 'show']);
    Route::get('mentors', [MentorController::class, 'index']);
    Route::get('mentors/{id}', [MentorController::class, 'show']);
    Route::post('logout', [LoginController::class, 'logout']);
    Route::get('appointments', [AppointmentController::class, 'index']);
    Route::delete('appointments/{id}', [AppointmentController::class, 'destroy']);

    // Activity Related Routes
    Route::get('activities', [ActivityController::class, 'index']);
    Route::get('activities/activityfile/download/{id}', [ActivityController::class, 'downloadActivityFile']);
    Route::get('activities/{id}', [ActivityController::class, 'show']);
    Route::get('submission/download/{id}', [SubmissionController::class, 'download']);
    Route::delete('activities/unsubmit/{id}', [SubmissionController::class, 'destroy']);

    // Startup Routes
    Route::get('startup/{id}', [StartupProfileController::class, 'show']);
    Route::get('startup', [StartupProfileController::class, 'index']);
    Route::put('update/startup/{id}', [StartupProfileController::class, 'update']);

    // Achievement routes
    Route::get('startup/achievements/{startup_profile}', [AchievementController::class, 'index']);
    Route::get('startup/achievements/{startup_profile_id}/{achievementId}', [AchievementController::class, 'show']);
    Route::post('startup/add/achievement', [AchievementController::class, 'store']);
    Route::put('startup/update/achievement/{startup_profile_id}/{achievementId}', [AchievementController::class, 'update']);
    Route::delete('startup/delete/achievement/{startup_profile_id}/{achievementId}', [AchievementController::class, 'destroy']);

    // Document Routes
    Route::get('documents/download/{documentId}', [DocumentController::class, 'download']);
    Route::get('documents/{startup_profile_id}', [DocumentController::class, 'index']);
    Route::get('documents/{startup_profile_id}/{documentType}', [DocumentController::class, 'show']);
   

    // Members Route
    Route::get('startup/members/{startup_profile_id}', [MemberController::class, 'index']);
});

// Mentor Routes for Admin
Route::middleware(['auth:sanctum', 'admin'])->group(function () {

    Route::post('mentors', [MentorController::class, 'store']);
    Route::delete('mentors/{id}', [MentorController::class, 'destroy']);
    Route::put('appointments/{id}', [AppointmentController::class, 'update']);
    Route::put('mentors/{id}', [MentorController::class, 'update']);

    // All Appointments
    Route::get('appointments/startup/{startup_profile_id}', [AppointmentController::class, 'getLeaderAppointments']);

    // Activity Related Routes
    Route::post('activities', [ActivityController::class, 'store']);
    Route::put('activities/{id}', [ActivityController::class, 'update']);
    Route::delete('activities/{id}', [ActivityController::class, 'destroy']);
    Route::get('activities/submissions/report', [ActivityController::class, 'activityReport']);
    Route::get('activities/submissions/{id}', [ActivityController::class, 'activitysubmission']);
    // Submission Routes
    Route::get('submissions/activities/{activityId}', [SubmissionController::class, 'index']);
    Route::get('submissions/activity/{id}', [SubmissionController::class, 'show']);
    Route::put('submissions/grade/{id}', [SubmissionController::class, 'gradeSubmission']);




    // Incubatees Routes
    Route::get('incubatees', [IncubateesController::class, 'index']);
    Route::get('incubatees/{id}', [IncubateesController::class, 'show']);
    Route::get('incubatees/startup/assign', [IncubateesController::class, 'incubateesWithoutStartupProfile']);

    // Register Incubatee Route
    Route::post('register/incubatee', [RegisterController::class, 'registerIncubatee']);
   

    // Startup Profile Routes
    Route::post('create/startup', [StartupProfileController::class, 'store']);
    Route::delete('delete/startup/{id}', [StartupProfileController::class, 'destroy']);
    Route::get('startup/details/{id}', [StartupProfileController::class, 'show']);

    // Members Routes
    Route::get('members/count/{startup_profile_id}', [MemberController::class, 'countMembers']);

    // Leader Routes
    Route::get('leaders', [LeaderController::class, 'index']);

    // Data Dashboard Reports Routes
    Route::get('data/startup/{id}', [DashboardDataController::class, 'getStartupProfile']);
    route::get('data/dashboard/admin', [DashboardDataController::class, 'getDashboardDetails']);
    
});


// Auth Routes Login and Register
Route::post('register', [RegisterController::class, 'register']);
Route::post('login', [LoginController::class, 'login']);