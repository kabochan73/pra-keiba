<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\RaceController;

use App\Http\Controllers\HorseController;

Route::get('/', [RaceController::class, 'index']);
Route::get('/races/{race}', [RaceController::class, 'show'])->name('races.show');
Route::get('/horses', [HorseController::class, 'show'])->name('horses.show');
