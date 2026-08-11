<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BusinessImportController;
use App\Http\Controllers\Api\BusinessController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\LeadController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\FollowupController;
use App\Http\Controllers\Api\EmailSenderController;
use App\Http\Controllers\Api\EmailTemplateController;
use App\Http\Controllers\Api\TemplateVariableController;
use App\Http\Controllers\Api\EmailCampaignController;
use App\Http\Controllers\Api\OAuthController;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

Route::post('/login', [AuthController::class, 'login']);

// OAuth Routes
Route::get('/oauth/google/redirect', [OAuthController::class, 'googleRedirect']);
Route::get('/oauth/google/callback', [OAuthController::class, 'googleCallback']);

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

        /*
        |--------------------------------------------------------------------------
        | Businesses
        |--------------------------------------------------------------------------
        */

        Route::get('/businesses', [BusinessController::class, 'index']);

        Route::get('/businesses/{business}', [BusinessController::class, 'show']);

        Route::post('/businesses/import', [BusinessImportController::class, 'import']);

        Route::post('/businesses/assign', [LeadController::class, 'assign']);

        /*
        |--------------------------------------------------------------------------
        | Users
        |--------------------------------------------------------------------------
        */

        Route::get('/users', [UserController::class, 'index']);

        Route::post('/users', [UserController::class, 'store']);

        Route::put('/users/{user}', [UserController::class, 'update']);

        Route::delete('/users/{user}', [UserController::class, 'destroy']);

        Route::get('/users/sales-executives', [UserController::class, 'salesExecutives']);
        
        Route::put(
            '/users/{user}/password',
            [UserController::class, 'updatePassword']
        );


        /*
        |--------------------------------------------------------------------------
        | Email Senders
        |--------------------------------------------------------------------------
        */

        Route::get('/email-senders', [EmailSenderController::class, 'index']);
        Route::post('/email-senders', [EmailSenderController::class, 'store']);
        Route::get('/email-senders/{emailSender}', [EmailSenderController::class, 'show']);
        Route::put('/email-senders/{emailSender}', [EmailSenderController::class, 'update']);
        Route::delete('/email-senders/{emailSender}', [EmailSenderController::class, 'destroy']);

        Route::post('/email-senders/{emailSender}/test', [EmailSenderController::class, 'test']);
        Route::post(
            '/email-senders/{emailSender}/send-test',
            [EmailSenderController::class, 'sendTest']
        );


        /*
        |--------------------------------------------------------------------------
        | Email Templates
        |--------------------------------------------------------------------------
        */

        Route::apiResource(
            'email-templates',
            EmailTemplateController::class
        );

        Route::post(
            'email-templates/{emailTemplate}/publish',
            [EmailTemplateController::class, 'publish']
        );

        Route::post(
            'email-templates/{emailTemplate}/duplicate',
            [EmailTemplateController::class, 'duplicate']
        );

        Route::get(
            'email-templates/{emailTemplate}/versions',
            [EmailTemplateController::class, 'versions']
        );



        /*
        |--------------------------------------------------------------------------
        | Template Variables
        |--------------------------------------------------------------------------
        */

        Route::get(
            'template-variables',
            [TemplateVariableController::class, 'index']
        );

        Route::post(
            'template-variables/preview',
            [TemplateVariableController::class, 'preview']
        );

        Route::post(
            'template-variables/render',
            [TemplateVariableController::class, 'render']
        );


        /*
        |--------------------------------------------------------------------------
        | Email Campaigns
        |--------------------------------------------------------------------------
        */

        Route::apiResource(
            'email-campaigns',
            EmailCampaignController::class
        );

        Route::post(
            'email-campaigns/{emailCampaign}/start',
            [EmailCampaignController::class, 'start']

        );

        Route::post(
            'email-campaigns/{emailCampaign}/pause',
            [EmailCampaignController::class, 'pause']
        );

        Route::post(
            'email-campaigns/{emailCampaign}/resume',
            [EmailCampaignController::class, 'resume']
        );

        Route::post(
            'email-campaigns/{emailCampaign}/cancel',
            [EmailCampaignController::class, 'cancel']
        );

        Route::post(
            'email-campaigns/{emailCampaign}/leads',
            [EmailCampaignController::class, 'assignLeads']
        );

        Route::get(
            'email-campaigns/{emailCampaign}/stats',
            [EmailCampaignController::class, 'stats']
        );

        Route::get(
            'email-campaigns/{emailCampaign}/leads',
            [EmailCampaignController::class, 'leads']
        );

        Route::post(
            'email-campaigns/{emailCampaign}/leads/retry-all',
            [EmailCampaignController::class, 'retryAllFailedLeads']
        );

        Route::post(
            'email-campaigns/{emailCampaign}/leads/{campaignLead}/retry',
            [EmailCampaignController::class, 'retryLead']
        );

        


    });

});