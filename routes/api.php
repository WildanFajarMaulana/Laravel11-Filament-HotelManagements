<?php

use App\Http\Controllers\Api\ReservationController;
use App\Http\Controllers\Api\RoomController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/register', [UserController::class, 'register']);
Route::post('/login', [UserController::class, 'login']);

Route::get('/rooms', [RoomController::class, 'index']);
Route::get('/rooms/{slug}', [RoomController::class, 'roomDetail']);
Route::middleware('auth:sanctum')->post('/create-rating/{reservation_id}', [RoomController::class, 'createRating']);

Route::middleware('auth:sanctum')->post('/create-reservation', [ReservationController::class, 'createReservation']);
Route::middleware('auth:sanctum')->get('/history-reservation', [ReservationController::class, 'historyReservationByUser']);
Route::middleware('auth:sanctum')->post('/cancel-reservation/{reservation_id}', [ReservationController::class, 'cancelReservation']);