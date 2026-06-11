<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\View\BooksController;
use App\Http\Controllers\View\CategoryController;
use App\Http\Controllers\View\ImageController;
use App\Http\Controllers\View\UserController;
use App\Http\Controllers\View\WebAuthController;

Route::view('/login', 'login')->middleware('guest')->name('login');

Route::post('/auth/session', [WebAuthController::class, 'establishSession'])->name('auth.session');
Route::post('/auth/logout', [WebAuthController::class, 'logout'])->name('auth.logout');

Route::middleware(['auth'])->group(function () {
    Route::get('/auth/token', [WebAuthController::class, 'issueToken'])->name('auth.token');

    Route::view('/home', 'dashboard.user.profile')->name('web.home');

    Route::get('/', function () {
        return redirect()->route('web.home');
    });

    Route::view('/profile', 'dashboard.user.profile')->name('dashboard.profile');

    Route::prefix('dashboard')->name('dashboard.')->group(function () {
        Route::middleware('role:admin')->group(function () {
            Route::resource('users', UserController::class);
        });

        Route::resource('books', BooksController::class);
        Route::resource('categories', CategoryController::class);
        Route::resource('images', ImageController::class);
    });
});
