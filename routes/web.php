<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\RaceController;

Route::get('/', [RaceController::class, 'index']);
Route::get('/races/{race}', [RaceController::class, 'show'])->name('races.show');
