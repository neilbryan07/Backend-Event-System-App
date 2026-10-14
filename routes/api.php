<?php

use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\EventController;
use App\Http\Controllers\Api\UserEventStatusController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Http\Controllers\Api\AdsController;     
use App\Http\Controllers\Api\CampaignController;
use Illuminate\Http\Response;
use App\Models\EventDay;

// Public routes
Route::post('register', [UserController::class,'register']);
Route::post('login', [UserController::class,'login']);
Route::get('/roles', [UserController::class, 'roleindex']);
Route::get('/branches', [CampaignController::class, 'branchList']);
Route::get('/event-qr/{id}', [EventController::class,'getQREvent']);
Route::post('611-login', [UserController::class,'externalLogin']);
Route::get('/Om-list', [UserController::class, 'getOm']);

// Authenticated routesphp 
Route::middleware(['auth:sanctum', 'throttle:5000,1'])->group(function () {

    Route::post('logout', [UserController::class,'logout']);
    Route::get('dashboard', [UserController::class,'dashboard']);
    Route::get('/oms', [UserController::class, 'listOMUsers']);
    Route::get('/event-status-types', [UserController::class, 'indexStatus']);
    Route::get('/user-stats/{user_id}', [UserController::class, 'userstats']);
    Route::post('/assign-role', [UserController::class, 'assignRole']);
   
    // Event routes
    Route::post('events/create', [EventController::class,'createEvent']); // admin
    Route::post('events/update/{id}', [EventController::class, 'updateEvent']); // admin
    Route::post('events/delete/{id}', [EventController::class, 'deleteEvent']); // admin
    Route::get('events', [EventController::class,'listEvents']); // admin  
    Route::post('events/scan', [EventController::class,'scanQr']); // admin  
    Route::get('/day-attendance-table', [EventController::class, 'getDayAttendanceTable']); // actual
    
    Route::post('pre-register', [UserEventStatusController::class,'preRegister']);
    Route::post('cancel-registration', [UserEventStatusController::class,'cancelRegistration']);
    Route::get('event-stats/{event_id}', [UserEventStatusController::class,'stats']); // admin
    Route::post('/toggle-verified', [UserEventStatusController::class, 'toggleUserEventVerified']);
    Route::post('/toggle-day-verified', [UserEventStatusController::class, 'toggleUserEventDayVerified']);
    // Ads routes
    Route::prefix('ads')->group(function () {
        Route::post('/create', [AdsController::class, 'storeAd']);
        Route::get('/show', [AdsController::class, 'index']);
        Route::post('/update', [AdsController::class, 'updateAd']);
        Route::post('/delete', [AdsController::class, 'deleteAd']);
    });

    // Campaigns routes
    Route::prefix('campaigns')->group(function () {
        Route::post('/create', [CampaignController::class, 'store']);
        Route::get('/list', [CampaignController::class, 'index']);
        Route::post('/update', [CampaignController::class, 'update']);
        Route::post('/delete', [CampaignController::class, 'destroy']);
    });

});