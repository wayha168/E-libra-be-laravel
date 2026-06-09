<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('web.login');
});

Route::get('/login', function () {
    return view('login');
})->name('web.login');

Route::get('/home', function () {
    return view('home');
})->name('web.home');
