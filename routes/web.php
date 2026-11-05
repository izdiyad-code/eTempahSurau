<?php

use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookerController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\ChairmanController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\PicController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public (Guest) - FR1
|--------------------------------------------------------------------------
*/
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/lang/{locale}', [LanguageController::class, 'switch'])->name('lang.switch');
Route::get('/announcements', [AnnouncementController::class, 'index'])->name('announcements.index');
Route::get('/calendar/events', [HomeController::class, 'calendarEvents'])->name('calendar.events');

/*
|--------------------------------------------------------------------------
| Guest only - FR2, FR3
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.store');
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.store');
});

/*
|--------------------------------------------------------------------------
| Any logged-in user
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/notifications', fn () => redirect()->route('announcements.index', ['tab' => 'notifications']))->name('notifications.index');
    Route::post('/notifications/read-all', [BookerController::class, 'markAllRead'])->name('notifications.readAll');

    Route::get('/profile', [BookerController::class, 'profile'])->name('profile.show');
    Route::put('/profile', [BookerController::class, 'updateProfile'])->name('profile.update');
});

/*
|--------------------------------------------------------------------------
| Public Booker - FR4, FR5, FR7, FR8
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:public,chairman'])->group(function () {
    // The Chairman may also add a booking; it is approved straight away.
    Route::get('/booking/create', [BookingController::class, 'create'])->name('bookings.create');
    Route::post('/booking', [BookingController::class, 'store'])->name('bookings.store');
});

Route::middleware(['auth', 'role:public'])->group(function () {
    Route::get('/my-bookings', [BookerController::class, 'index'])->name('booker.bookings.index');
    Route::get('/my-bookings/{booking}', [BookerController::class, 'show'])->name('booker.bookings.show');
    Route::get('/my-bookings/{booking}/proof', [BookerController::class, 'proof'])->name('booker.deposit.proof');
    Route::post('/my-bookings/{booking}/deposit', [BookerController::class, 'submitDeposit'])->name('booker.deposit.submit');
    Route::post('/my-bookings/{booking}/change', [BookerController::class, 'requestChange'])->name('booker.change.store');
});

/*
|--------------------------------------------------------------------------
| Chairman only - FR10
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:chairman'])->prefix('chairman')->group(function () {
    Route::get('/bookings', [ChairmanController::class, 'index'])->name('chairman.bookings.index');
    Route::post('/bookings/{booking}/approve', [ChairmanController::class, 'approve'])->name('chairman.bookings.approve');
    Route::post('/bookings/{booking}/reject', [ChairmanController::class, 'reject'])->name('chairman.bookings.reject');

    // Manage Secretary / PIC accounts
    Route::post('/pics', [UserController::class, 'storePic'])->name('chairman.pics.store');
    Route::delete('/pics/{user}', [UserController::class, 'destroyPic'])->name('chairman.pics.destroy');
});

/*
|--------------------------------------------------------------------------
| PIC only - FR8, FR9
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:pic'])->prefix('pic')->group(function () {
    Route::get('/bookings', [PicController::class, 'bookings'])->name('pic.bookings');
    Route::post('/bookings/{booking}/complete', [PicController::class, 'complete'])->name('pic.bookings.complete');

    Route::get('/deposits', [PicController::class, 'deposits'])->name('pic.deposits');
    Route::get('/deposits/{deposit}/proof', [PicController::class, 'proof'])->name('pic.deposits.proof');
    Route::post('/deposits/{deposit}/verify', [PicController::class, 'verifyDeposit'])->name('pic.deposits.verify');
    Route::post('/deposits/{deposit}/settle', [PicController::class, 'settleDeposit'])->name('pic.deposits.settle');

    Route::get('/facilities', [PicController::class, 'facilities'])->name('pic.facilities');
    Route::post('/facilities', [PicController::class, 'storeFacility'])->name('pic.facilities.store');
    Route::put('/facilities/{facility}', [PicController::class, 'updateFacility'])->name('pic.facilities.update');
    Route::post('/event-types', [PicController::class, 'storeEventType'])->name('pic.eventtypes.store');
    Route::put('/event-types/{eventType}', [PicController::class, 'updateEventType'])->name('pic.eventtypes.update');
    Route::post('/equipment', [PicController::class, 'storeEquipment'])->name('pic.equipment.store');
    Route::put('/equipment/{equipment}', [PicController::class, 'updateEquipment'])->name('pic.equipment.update');

    Route::get('/blocks', [PicController::class, 'blocks'])->name('pic.blocks');
    Route::post('/blocks', [PicController::class, 'storeBlock'])->name('pic.blocks.store');
    Route::delete('/blocks/{block}', [PicController::class, 'destroyBlock'])->name('pic.blocks.destroy');
});

/*
|--------------------------------------------------------------------------
| PIC + Chairman shared - FR5 review, FR11 reports
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:pic,chairman'])->prefix('staff')->group(function () {
    Route::get('/bookings/{booking}', [PicController::class, 'showBooking'])->name('staff.bookings.show');
    Route::get('/change-requests', [PicController::class, 'changeRequests'])->name('staff.changes');
    Route::post('/change-requests/{changeRequest}', [PicController::class, 'reviewChange'])->name('staff.changes.review');
    Route::get('/users', [UserController::class, 'index'])->name('staff.users');

    // Announcements / events for everyone
    Route::post('/announcements', [AnnouncementController::class, 'store'])->name('announcements.store');
    Route::put('/announcements/{announcement}', [AnnouncementController::class, 'update'])->name('announcements.update');
    Route::delete('/announcements/{announcement}', [AnnouncementController::class, 'destroy'])->name('announcements.destroy');
    Route::get('/reports', [PicController::class, 'reports'])->name('staff.reports');
    Route::get('/reports/export', [PicController::class, 'exportReport'])->name('staff.reports.export');
});
