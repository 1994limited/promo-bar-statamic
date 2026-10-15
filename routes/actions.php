<?php

use Illuminate\Support\Facades\Route;
use NineteenNinetyFour\PromoBar\Http\Controllers\ActionsController;

// At /!/promo-bar/...

Route::get('promo-bar.css', [ActionsController::class, 'stylesheet'])->name('stylesheet');
Route::get('promo-bar.js', [ActionsController::class, 'script'])->name('script');
Route::get('state', [ActionsController::class, 'state'])->middleware('throttle:60,1')->name('state');
