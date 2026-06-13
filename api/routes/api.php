<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\API\HomeController;
use App\Http\Controllers\API\VenueController;
use App\Http\Controllers\API\BookingController;
use App\Http\Controllers\API\PaymentController;
use App\Http\Controllers\API\ReviewController;
use App\Http\Controllers\API\ProfileController;

/*
|--------------------------------------------------------------------------
| API Routes — /api/v1/
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {

    // ── Auth (public) ────────────────────────────────────────────────────
    Route::prefix('auth')->group(function () {
        Route::post('send-otp',    [AuthController::class, 'sendOtp']);
        Route::post('verify-otp',  [AuthController::class, 'verifyOtp']);
        Route::post('register',    [AuthController::class, 'register']);
        Route::post('login',       [AuthController::class, 'login']);
        Route::post('login-otp',   [AuthController::class, 'loginWithOtp']);
    });

    // ── Home & Discovery (public) ─────────────────────────────────────────
    Route::get('home',       [HomeController::class, 'index']);
    Route::get('categories', [HomeController::class, 'categories']);

    // ── Venues (public) ──────────────────────────────────────────────────
    Route::prefix('venues')->group(function () {
        Route::get('/',              [VenueController::class, 'index']);
        Route::get('featured',       [VenueController::class, 'featured']);
        Route::get('nearby',         [VenueController::class, 'nearby']);
        Route::get('{id}',           [VenueController::class, 'show']);
        Route::get('{id}/availability', [VenueController::class, 'availability']);
        Route::get('{id}/reviews',   [ReviewController::class, 'index']);
    });

    // ── Payment webhooks (no auth) ────────────────────────────────────────
    Route::prefix('payment')->group(function () {
        Route::post('payme/merchant', [PaymentController::class, 'paymeMerchant']);
        Route::post('click/prepare',  [PaymentController::class, 'clickPrepare']);
        Route::post('click/complete', [PaymentController::class, 'clickComplete']);
    });

    // ── Authenticated routes ──────────────────────────────────────────────
    Route::middleware('auth:api')->group(function () {

        // Auth
        Route::prefix('auth')->group(function () {
            Route::post('logout',  [AuthController::class, 'logout']);
            Route::post('refresh', [AuthController::class, 'refresh']);
            Route::get('me',       [AuthController::class, 'me']);
        });

        // Profile
        Route::prefix('profile')->group(function () {
            Route::get('/',                   [ProfileController::class, 'show']);
            Route::put('/',                   [ProfileController::class, 'update']);
            Route::put('password',            [ProfileController::class, 'changePassword']);
            Route::put('fcm-token',           [ProfileController::class, 'updateFcmToken']);
            Route::get('favorites',           [ProfileController::class, 'favorites']);
            Route::post('favorites/{venueId}', [ProfileController::class, 'toggleFavorite']);
            Route::get('notifications',       [ProfileController::class, 'notifications']);
            Route::post('notifications/read-all', [ProfileController::class, 'markAllRead']);
        });

        // Bookings
        Route::prefix('bookings')->group(function () {
            Route::get('/',         [BookingController::class, 'index']);
            Route::post('/',        [BookingController::class, 'store']);
            Route::get('{id}',      [BookingController::class, 'show']);
            Route::post('{id}/cancel', [BookingController::class, 'cancel']);
        });

        // Payments
        Route::prefix('payment')->group(function () {
            Route::get('payme/url/{bookingId}',  [PaymentController::class, 'paymeUrl']);
            Route::get('click/url/{bookingId}',  [PaymentController::class, 'clickUrl']);
            Route::get('history',                [PaymentController::class, 'history']);
        });

        // Reviews
        Route::post('reviews',          [ReviewController::class, 'store']);
        Route::post('reviews/{id}/reply', [ReviewController::class, 'reply']);

        // ── Owner routes ──────────────────────────────────────────────
        Route::prefix('owner')->middleware('role:owner,admin')->group(function () {
            Route::get('bookings',     [BookingController::class, 'ownerBookings']);
            Route::post('venues',      [VenueController::class, 'store']);
            Route::put('venues/{id}',  [VenueController::class, 'update']);
        });
    });
});
