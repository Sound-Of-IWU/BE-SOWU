<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ActivityController;
use App\Http\Controllers\VismisController;
use App\Http\Controllers\AuthController;

// Auth routes
Route::post('auth/login', [AuthController::class, 'login']);
Route::post('auth/logout', [AuthController::class, 'logout'])->middleware('auth:api');
Route::post('auth/refresh', [AuthController::class, 'refresh'])->middleware('auth:api');

// Users
Route::get('user', [UserController::class, 'index']);
Route::get('user/{id}', [UserController::class, 'show']);
Route::post('user', [UserController::class, 'store']);
Route::post('user/join', [UserController::class, 'join']);
Route::put('user/{id}', [UserController::class, 'update']);
Route::patch('user/{id}/password', [UserController::class, 'updatePassword']);
Route::patch('user/{id}/profile-photo', [UserController::class, 'updateProfilePhoto']);
Route::delete('user/{id}', [UserController::class, 'destroy']);

// Activity
Route::get('activity', [ActivityController::class, 'index']);
Route::get('activity/{id}', [ActivityController::class, 'show']);
Route::post('activity', [ActivityController::class, 'store']);
Route::put('activity/{id}', [ActivityController::class, 'update']);
Route::patch('activity/{id}/image', [ActivityController::class, 'updateImage']);
Route::delete('activity/{id}', [ActivityController::class, 'destroy']);

// Vismis
Route::get('vismis', [VismisController::class, 'index']);
Route::get('vismis/{id}', [VismisController::class, 'show']);
Route::put('vismis/{id}', [VismisController::class, 'update']);
Route::patch('vismis/{id}/image', [VismisController::class, 'updateImage']);
