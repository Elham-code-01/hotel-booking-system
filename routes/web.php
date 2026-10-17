<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HotelController;
use App\Http\Controllers\HotelManageController;
use App\Http\Controllers\OtherChargeController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RepresentativeController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\UserAdminController;
use App\Http\Middleware\EnsureRouteAccess;
use App\Http\Middleware\EnsureUserIsActive;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::middleware(['auth', EnsureUserIsActive::class, EnsureRouteAccess::class])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::get('/users', [UserAdminController::class, 'index'])->name('users.index');
    Route::patch('/users/{user}', [UserAdminController::class, 'update'])->name('users.update');

    // Placeholders: each one is replaced by a real controller in a later step.
    Route::get('/hotels/{hotel}/bookings', [BookingController::class, 'index'])->name('bookings.index');
    Route::get('/bookings/new', [BookingController::class, 'create'])->name('bookings.create');
    Route::post('/bookings', [BookingController::class, 'store'])->name('bookings.store');
    Route::get('/bookings/{booking}/edit', [BookingController::class, 'edit'])->name('bookings.edit');
    Route::patch('/bookings/{booking}', [BookingController::class, 'update'])->name('bookings.update');

    Route::get('/hotels', [HotelController::class, 'index'])->name('hotels');
    Route::get('/hotels/new', [HotelManageController::class, 'create'])->name('hotels.create');
    Route::post('/hotels', [HotelManageController::class, 'store'])->name('hotels.store');
    Route::get('/hotels/{hotel}/manage', [HotelManageController::class, 'show'])->name('hotels.manage');
    Route::patch('/hotels/{hotel}', [HotelManageController::class, 'update'])->name('hotels.update');
    Route::post('/hotels/{hotel}/rooms', [RoomController::class, 'store'])->name('rooms.store');
    Route::patch('/rooms/{room}', [RoomController::class, 'update'])->name('rooms.update');

    Route::get('/companies', [CompanyController::class, 'index'])->name('companies');
    Route::post('/companies', [CompanyController::class, 'store'])->name('companies.store');
    Route::patch('/companies/{company}', [CompanyController::class, 'update'])->name('companies.update');
    Route::delete('/companies/{company}', [CompanyController::class, 'destroy'])->name('companies.destroy');

    Route::get('/accounts', [AccountController::class, 'overview'])->name('accounts');
    Route::get('/company-account', [AccountController::class, 'company'])->name('company-account');

    Route::get('/payments', [PaymentController::class, 'index'])->name('payments');
    Route::post('/payments', [PaymentController::class, 'store'])->name('payments.store');
    Route::delete('/payments/{payment}', [PaymentController::class, 'destroy'])->name('payments.destroy');

    Route::get('/other-charges', [OtherChargeController::class, 'index'])->name('other-charges');
    Route::post('/other-charges', [OtherChargeController::class, 'store'])->name('other-charges.store');
    Route::delete('/other-charges/{otherCharge}', [OtherChargeController::class, 'destroy'])->name('other-charges.destroy');

    Route::get('/settings', [SettingsController::class, 'edit'])->name('settings');
    Route::patch('/settings', [SettingsController::class, 'update'])->name('settings.update');

    Route::get('/representative', [RepresentativeController::class, 'index'])->name('representative');
    Route::post('/representative/rooms', [RepresentativeController::class, 'update'])->name('representative.update');
    Route::view('/reports', 'pages.placeholder', ['title' => 'Reports'])->name('reports');

    // No self-delete route: an administrator must not be able to remove their own account.
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
});

require __DIR__.'/auth.php';
