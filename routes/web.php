<?php

use App\Http\Controllers\ParseHomeownersController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::post('/submit-names', ParseHomeownersController::class);
