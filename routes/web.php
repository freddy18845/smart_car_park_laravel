<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ParkingSpaceController;
Route::get('/', function () {
    return view('welcome');
});


Route::get('parking-spaces', [ParkingSpaceController::class, 'index']);
Route::get('parking-spaces/{id}', [ParkingSpaceController::class, 'show']);


