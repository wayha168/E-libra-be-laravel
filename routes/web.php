<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\View\BooksController;
use App\Http\Controllers\View\CategoryController;
use App\Http\Controllers\View\ImageController;
use App\Http\Controllers\View\UserController;
use App\Http\Controllers\View\WebAuthController;
use App\Http\Controllers\View\PermissionController;

Route::view('/login', 'login')->middleware('guest')->name('login');

Route::post('/auth/session', [WebAuthController::class, 'establishSession'])->name('auth.session');
Route::post('/auth/logout', [WebAuthController::class, 'logout'])->name('auth.logout');

Route::middleware(['auth'])->group(function () {
    Route::get('/auth/token', [WebAuthController::class, 'issueToken'])->name('auth.token');

    // Block role "user" from using the dashboard (only admin can access)
    Route::middleware('role:admin')->group(function () {
        Route::view('/home', 'dashboard.index')->name('dashboard.index');
        Route::view('/profile', 'dashboard.user.profile')->name('dashboard.profile');

        Route::prefix('dashboard')->name('dashboard.')->group(function () {
            Route::middleware('role:admin')->group(function () {
                Route::resource('users', UserController::class);
                Route::resource('permissions', PermissionController::class);
            });

            Route::resource('books', BooksController::class);
            Route::resource('categories', CategoryController::class);
            Route::resource('images', ImageController::class);
            Route::resource('authors', \App\Http\Controllers\View\AuthorsController::class);
            Route::get('/authors/{author}/books', [\App\Http\Controllers\View\AuthorsController::class, 'books'])
                ->name('authors.books');
        });
    });
});
