<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/televisions', function () {
    return view('app');
});

Route::get('/tv-sprejemniki', function () {
    return view('app');
});
