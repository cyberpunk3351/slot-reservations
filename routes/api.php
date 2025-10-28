<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AvailabilityController;
use App\Http\Controllers\Api\HoldController;

Route::get('/slots/availability', [AvailabilityController::class, 'index']);

Route::post('/slots/{id}/hold', [HoldController::class, 'store']);
Route::post('/holds/{id}/confirm', [HoldController::class, 'confirm']);
Route::delete('/holds/{id}', [HoldController::class, 'destroy']);
