<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\VenueController;
use App\Http\Controllers\Admin\BookingController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\ReviewController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\BannerController;

// ── Auth ─────────────────────────────────────────────────────────────────
Route::get('login',  [AuthController::class, 'showLogin'])->name('admin.login');
Route::post('login', [AuthController::class, 'login'])->name('admin.login.post');
Route::post('logout',[AuthController::class, 'logout'])->name('admin.logout');

// ── Admin Panel ───────────────────────────────────────────────────────────
Route::middleware(['auth', 'admin'])->group(function () {

    Route::get('/', [DashboardController::class, 'index'])->name('admin.dashboard');

    // Venues
    Route::prefix('venues')->name('admin.venues.')->group(function () {
        Route::get('/',               [VenueController::class, 'index'])->name('index');
        Route::get('{id}',            [VenueController::class, 'show'])->name('show');
        Route::get('{id}/edit',       [VenueController::class, 'edit'])->name('edit');
        Route::put('{id}',            [VenueController::class, 'update'])->name('update');
        Route::delete('{id}',         [VenueController::class, 'destroy'])->name('destroy');
        Route::post('{id}/approve',   [VenueController::class, 'approve'])->name('approve');
        Route::post('{id}/reject',    [VenueController::class, 'reject'])->name('reject');
        Route::post('{id}/featured',  [VenueController::class, 'toggleFeatured'])->name('featured');
    });

    // Bookings
    Route::prefix('bookings')->name('admin.bookings.')->group(function () {
        Route::get('/',               [BookingController::class, 'index'])->name('index');
        Route::get('/export',         [BookingController::class, 'export'])->name('export');
        Route::get('{id}',            [BookingController::class, 'show'])->name('show');
        Route::post('{id}/status',    [BookingController::class, 'updateStatus'])->name('status');
    });

    // Users
    Route::prefix('users')->name('admin.users.')->group(function () {
        Route::get('/',               [UserController::class, 'index'])->name('index');
        Route::get('create',          [UserController::class, 'create'])->name('create');
        Route::post('/',              [UserController::class, 'store'])->name('store');
        Route::get('{id}',            [UserController::class, 'show'])->name('show');
        Route::get('{id}/edit',       [UserController::class, 'edit'])->name('edit');
        Route::put('{id}',            [UserController::class, 'update'])->name('update');
        Route::post('{id}/block',     [UserController::class, 'toggleBlock'])->name('block');
    });

    // Payments
    Route::prefix('payments')->name('admin.payments.')->group(function () {
        Route::get('/',               [PaymentController::class, 'index'])->name('index');
        Route::get('{id}',            [PaymentController::class, 'show'])->name('show');
    });

    // Reviews
    Route::prefix('reviews')->name('admin.reviews.')->group(function () {
        Route::get('/',               [ReviewController::class, 'index'])->name('index');
        Route::post('{id}/toggle',    [ReviewController::class, 'toggleVisibility'])->name('toggle');
        Route::delete('{id}',         [ReviewController::class, 'destroy'])->name('destroy');
    });

    // Categories
    Route::prefix('categories')->name('admin.categories.')->group(function () {
        Route::get('/',               [CategoryController::class, 'index'])->name('index');
        Route::get('create',          [CategoryController::class, 'create'])->name('create');
        Route::post('/',              [CategoryController::class, 'store'])->name('store');
        Route::get('{id}/edit',       [CategoryController::class, 'edit'])->name('edit');
        Route::put('{id}',            [CategoryController::class, 'update'])->name('update');
        Route::delete('{id}',         [CategoryController::class, 'destroy'])->name('destroy');
    });

    // Banners
    Route::prefix('banners')->name('admin.banners.')->group(function () {
        Route::get('/',               [BannerController::class, 'index'])->name('index');
        Route::get('create',          [BannerController::class, 'create'])->name('create');
        Route::post('/',              [BannerController::class, 'store'])->name('store');
        Route::get('{id}/edit',       [BannerController::class, 'edit'])->name('edit');
        Route::put('{id}',            [BannerController::class, 'update'])->name('update');
        Route::delete('{id}',         [BannerController::class, 'destroy'])->name('destroy');
    });
});
