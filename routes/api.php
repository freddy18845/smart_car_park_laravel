<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ParkingSpaceController;
use App\Http\Controllers\Api\ParkingSpotController;
use App\Http\Controllers\Api\SubSpaceController;
use App\Http\Controllers\Api\ReservationController;

// Authentication routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Public routes
Route::get('/parking-spaces', [ParkingSpaceController::class, 'index']);
Route::get('/parking-space/{id}', [ParkingSpaceController::class, 'show']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
 
    
    // Parking Spaces
    Route::post('/parking-spaces', [ParkingSpaceController::class, 'store']);
    Route::put('/parking-spaces/{id}', [ParkingSpaceController::class, 'update']);
    Route::delete('/parking-spaces/{id}', [ParkingSpaceController::class, 'destroy']);
    Route::get('/parking-spaces/operator/{id}', [ParkingSpaceController::class, 'getByOperator']);

    // Parking Spots
    Route::put('/parking-spots/{id}', [ParkingSpotController::class, 'update']);
    Route::delete('/parking-spots/{id}', [ParkingSpotController::class, 'destroy']);

    // Sub Spaces
    Route::put('/subspaces/{id}', [SubSpaceController::class, 'update']);
    Route::delete('/subspaces/{id}', [SubSpaceController::class, 'destroy']);
    // Update single sub-space status
    Route::put('sub-spaces/{id}/status', [SubSpaceController::class, 'updateStatus']);
    
    // Bulk update sub-spaces status
    Route::post('sub-spaces/bulk-update-status', [SubSpaceController::class, 'bulkUpdateStatus']);
    
    // Toggle sub-space status (available <-> occupied)
    Route::post('sub-spaces/{id}/toggle-status', [SubSpaceController::class, 'toggleStatus']);
    
    // Get sub-space details
    Route::get('sub-spaces/{id}', [SubSpaceController::class, 'show']);
    
    // Get all sub-spaces for a parking spot
    Route::get('parking-spots/{parkingSpotId}/sub-spaces', [SubSpaceController::class, 'getByParkingSpot']);

    // Reservations
    Route::post('/reservations', [ReservationController::class, 'store']);
    Route::get('/reservations', [ReservationController::class, 'index']);
    Route::get('/reservations/filter', [ReservationController::class, 'filter']);
    Route::get('/reservations/{id}', [ReservationController::class, 'show']);
    Route::put('/reservations/{id}', [ReservationController::class, 'update']); // Updates end_time, status, and space status
    Route::delete('/reservations/{id}', [ReservationController::class, 'cancel']);
    Route::post('/reservations/update-location', [ReservationController::class, 'updateActiveReservationLocation']);
    
    // Specific operations
    //Route::patch('/reservations/{id}/status', [ReservationController::class, 'updateStatus']);
    Route::patch('/reservations/{id}/extend', [ReservationController::class, 'extendTime']);
    Route::post('/check-availability', [ReservationController::class, 'checkAvailability']);
});
