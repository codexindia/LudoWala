<?php

use App\Http\Controllers\Api\AuthManager;
use App\Http\Controllers\Api\Payment\RazropayManager;
use App\Http\Controllers\Api\ProfileManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::prefix('auth')->controller(AuthManager::class)->group(function () {
    Route::post('sendOTP', 'sendOTP');
    Route::post('loginOrSignup','loginOrSignup');
});
Route::middleware('auth:sanctum')->group(function(){
    Route::prefix('profile')->controller(ProfileManager::class)->group(function () {
        Route::post('getUser', 'getUser');
        Route::post('profileUpdate', 'profileUpdate');
    });  
    Route::prefix('wallet')->controller(RazropayManager::class)->group(function () {
        Route::post('deposit', 'depositAmount');
        Route::post('deposit/razorpay/webhook/handel', 'RazorppaywebHookHander');
    });  
});
