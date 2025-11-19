<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Spa\LandingPageController;

Route::get('/', [LandingPageController::class, 'home'])->name('landing.home');