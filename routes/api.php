<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\VideoController;

Route::post('/upload-video', [VideoController::class, 'upload']);
Route::post('/delete-video', [VideoController::class, 'delete']);

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');
