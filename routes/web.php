<?php

use App\Http\Controllers\JacadApiController;
use App\Http\Controllers\LogController;
use App\Http\Controllers\ManualSyncController;
use App\Http\Controllers\MoodleCourseController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PromotionController;
use App\Http\Controllers\SendZeroController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route(Auth::check() ? 'dashboard' : 'login');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', fn () => view('auth.login'))->name('login');
    Route::get('/register', fn () => view('auth.register'))->name('register');
});

// Grupo protegido por autenticação
// Route::middleware(['auth', 'verified'])->group(function () {
Route::middleware([])->group(function () {
    Route::get('/dashboard', fn () => view('dashboard'))->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Rotas de Promoções
    Route::prefix('promotions')->name('promotions.')->group(function () {
        Route::get('/', [PromotionController::class, 'index'])->name('index');
        Route::get('/{promotion}', [PromotionController::class, 'show'])->name('show');
        Route::delete('/{promotion}', [PromotionController::class, 'destroy'])->name('destroy');

        // Ações via AJAX
        Route::post('/scrape', [PromotionController::class, 'scrape'])->name('scrape');
        Route::post('/{promotion}/approve', [PromotionController::class, 'approve'])->name('approve');
        Route::post('/{promotion}/reject', [PromotionController::class, 'reject'])->name('reject');
        Route::post('/{promotion}/regenerate-post', [PromotionController::class, 'regeneratePost'])->name('regenerate-post');
        Route::post('/{promotion}/generate-variations', [PromotionController::class, 'generateVariations'])->name('generate-variations');
        Route::put('/{promotion}/update-post', [PromotionController::class, 'updatePost'])->name('update-post');
        Route::post('/bulk-action', [PromotionController::class, 'bulkAction'])->name('bulk-action');
    });
});

require __DIR__ . '/auth.php';
