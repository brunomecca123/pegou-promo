<?php

use App\Http\Controllers\Api\PromotionController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PromotionPanelController;

Route::get('/', function () {
    return view('welcome');
});


Route::prefix('painel')->group(function () {
    Route::get('/', [PromotionPanelController::class, 'create'])->name('painel.create');
    Route::post('/cadastrar', [PromotionPanelController::class, 'store'])->name('painel.store');
});


