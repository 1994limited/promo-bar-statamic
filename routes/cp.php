<?php

use Illuminate\Support\Facades\Route;
use NineteenNinetyFour\PromoBar\Http\Controllers\CP\BarsController;

Route::prefix('promo-bars')->name('promo-bar.')->group(function () {
    Route::get('/', [BarsController::class, 'index'])->name('index');
    Route::get('create', [BarsController::class, 'create'])->name('create');
    Route::post('/', [BarsController::class, 'store'])->name('store');
    Route::post('reorder', [BarsController::class, 'reorder'])->name('reorder');
    Route::get('{bar}', [BarsController::class, 'edit'])->name('edit');
    Route::patch('{bar}', [BarsController::class, 'update'])->name('update');
    Route::delete('{bar}', [BarsController::class, 'destroy'])->name('destroy');
    Route::post('{bar}/duplicate', [BarsController::class, 'duplicate'])->name('duplicate');
    Route::post('{bar}/toggle', [BarsController::class, 'toggle'])->name('toggle');
});
