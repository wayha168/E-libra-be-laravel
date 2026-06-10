<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BooksController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ImageController;

Route::get('/', function () {
    return redirect()->route('web.login');
});

Route::get('/login', function () {
    return view('login');
})->name('web.login');

Route::get('/login', function () {
    return view('login');
})->name('login');

Route::get('/home', function () {
    return redirect()->route('dashboard.profile');
})->name('web.home');


Route::get('/dashboard/profile', function () {
    return view('dashboard.profile');
})->name('dashboard.profile');


Route::prefix('dashboard')->name('dashboard.')->group(function () {
    Route::resource('books', BooksController::class);
    Route::resource('categories', CategoryController::class);
    Route::resource('images', ImageController::class);
});
