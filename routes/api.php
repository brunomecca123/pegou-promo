<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PromotionController;

Route::post('/promotions', [PromotionController::class, 'store']);
