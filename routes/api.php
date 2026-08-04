<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BusinessImportController;
use App\Http\Controllers\Api\BusinessController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\LeadController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\FollowupController;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

Route::post('/login', [AuthController::class, 'login']);

/*
|--------------------------------------------------------------------------
| Protected Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Common
    |--------------------------------------------------------------------------
    */

    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/dashboard', [DashboardController::class, 'index']);

    /*
    |--------------------------------------------------------------------------
    | My Leads
    |--------------------------------------------------------------------------
    */

    Route::get('/my-leads', [LeadController::class, 'myLeads']);

    Route::get('/my-leads/{business}', [LeadController::class, 'show']);

    Route::post('/my-leads/{business}/call', [LeadController::class, 'call']);

    /*
    |--------------------------------------------------------------------------
    | Followups
    |--------------------------------------------------------------------------
    */

    Route::get('/followups/today', [FollowupController::class, 'today']);
    Route::get('/followups/upcoming', [FollowupController::class, 'upcoming']);
    Route::get('/followups/overdue', [FollowupController::class, 'overdue']);

    /*
    |--------------------------------------------------------------------------
    | Super Admin
    |--------------------------------------------------------------------------
    */

    Route::middleware('role:super_admin')->group(function () {

        Route::get('/businesses', [BusinessController::class, 'index']);

        Route::get('/businesses/{business}', [BusinessController::class, 'show']);

        Route::post('/businesses/import', [BusinessImportController::class, 'import']);

        Route::post('/businesses/assign', [LeadController::class, 'assign']);

        Route::get('/users/sales-executives', [UserController::class, 'salesExecutives']);

    });

});