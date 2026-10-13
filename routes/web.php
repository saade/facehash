<?php

use Saade\Facehash\Http\Controllers\FacehashController;
use Illuminate\Support\Facades\Route;

Route::middleware(config('facehash.route.middleware', []))
    ->get(config('facehash.route.prefix', 'facehash'), FacehashController::class)
    ->name('facehash');
