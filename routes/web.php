<?php

use Illuminate\Support\Facades\Route;

Route::get('/upload-video', function () {
    return view('welcome');
});
Route::get('test',function () {
    return view('test');
});

