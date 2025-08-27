<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PromotionController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route(Auth::check() ? 'dashboard' : 'login');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', function () {
        return view('auth.login');
    })->name('login');

    Route::get('/register', function () {
        return view('auth.register');
    })->name('register');
});

// Rota de teste do pipeline completo (sem middleware para testes)
Route::get('/test-pipeline', function () {
    try {
        $processor = app(\App\Services\PromotionProcessorService::class);
        $result = $processor->processNewPromotions('amazon', 5);
        
        return response()->json($result);
        
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
    }
})->name('test.pipeline');

// Rota para forçar processamento de novos produtos (ignora duplicatas por 1 hora)
Route::get('/test-force-new', function () {
    try {
        // Vamos processar manualmente para testar
        $scrapper = app(\App\Services\PromobitScrapperService::class);
        $amazonService = app(\App\Services\AmazonService::class);
        $geminiService = app(\App\Services\GeminiService::class);
        
        $promotions = $scrapper->scrapePromotions('amazon', 5);
        
        if (empty($promotions)) {
            return response()->json([
                'success' => false,
                'error' => 'Nenhuma promoção encontrada'
            ]);
        }
        
        $processed = [];
        $skipped = [];
        $errors = [];
        
        foreach ($promotions as $promotion) {
            try {
                // Verificar duplicatas apenas das últimas 2 horas
                $existingPromotion = \App\Models\Promotion::where('title', $promotion['title'])
                    ->where('created_at', '>', now()->subHours(2))
                    ->first();
                
                if ($existingPromotion) {
                    $skipped[] = "Produto '{$promotion['title']}' foi postado recentemente";
                    continue;
                }
                
                // Gerar link de afiliado se for Amazon
                $affiliateUrl = null;
                if (!empty($promotion['source_url'])) {
                    $affiliateUrl = $amazonService->generateAffiliateLink($promotion['source_url']);
                }
                
                // Gerar post com IA
                $aiPost = $geminiService->generatePromotionPost($promotion);
                
                // Criar promoção
                $promotionModel = \App\Models\Promotion::create([
                    'title' => $promotion['title'],
                    'description' => $promotion['description'],
                    'url' => $affiliateUrl ?: $promotion['source_url'],
                    'source_url' => $promotion['source_url'],
                    'image' => $promotion['image'],
                    'original_price' => $promotion['original_price'],
                    'discounted_price' => $promotion['discounted_price'],
                    'discount_percentage' => $promotion['discount_percentage'],
                    'store' => $promotion['store'],
                    'category' => $promotion['category'],
                    'status' => 'pending',
                    'ai_generated_post' => $aiPost
                ]);
                
                $processed[] = [
                    'id' => $promotionModel->id,
                    'title' => $promotion['title'],
                    'affiliate_url' => $affiliateUrl,
                    'ai_post_preview' => substr($aiPost ?: 'Post não gerado', 0, 150) . '...',
                    'has_affiliate' => $affiliateUrl !== $promotion['source_url']
                ];
                
            } catch (\Exception $e) {
                $errors[] = "Erro ao processar '{$promotion['title']}': " . $e->getMessage();
            }
        }
        
        return response()->json([
            'success' => true,
            'total_scraped' => count($promotions),
            'processed' => $processed,
            'skipped' => $skipped,
            'errors' => $errors,
            'summary' => [
                'new_products' => count($processed),
                'skipped_recent' => count($skipped),
                'failed' => count($errors)
            ]
        ]);
        
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
    }
// Rota para testar com produtos completamente novos (deleta alguns e reprocessa)
Route::get('/test-fresh-products', function () {
    try {
        // Deletar as últimas 3 promoções para testar o fluxo completo
        $lastPromotions = \App\Models\Promotion::orderBy('created_at', 'desc')->limit(3)->get();
        $deletedTitles = [];
        
        foreach ($lastPromotions as $promo) {
            $deletedTitles[] = $promo->title;
            $promo->delete();
        }
        
        // Agora processar novamente
        $scrapper = app(\App\Services\PromobitScrapperService::class);
        $amazonService = app(\App\Services\AmazonService::class);
        $geminiService = app(\App\Services\GeminiService::class);
        
        $promotions = $scrapper->scrapePromotions('amazon', 3);
        
        if (empty($promotions)) {
            return response()->json([
                'success' => false,
                'error' => 'Nenhuma promoção encontrada'
            ]);
        }
        
        $processed = [];
        $errors = [];
        
        foreach ($promotions as $promotion) {
            try {
                // Gerar link de afiliado se for Amazon
                $affiliateUrl = null;
                if (!empty($promotion['source_url'])) {
                    $affiliateUrl = $amazonService->generateAffiliateLink($promotion['source_url']);
                }
                
                // Gerar post com IA
                $aiPost = null;
                try {
                    $aiPost = $geminiService->generatePromotionPost($promotion);
                } catch (\Exception $e) {
                    // Se falhar na IA, continua sem o post
                    $aiPost = "Post não pôde ser gerado: " . $e->getMessage();
                }
                
                // Criar promoção
                $promotionModel = \App\Models\Promotion::create([
                    'title' => $promotion['title'],
                    'description' => $promotion['description'],
                    'url' => $affiliateUrl ?: $promotion['source_url'],
                    'source_url' => $promotion['source_url'],
                    'image' => $promotion['image'],
                    'original_price' => $promotion['original_price'],
                    'discounted_price' => $promotion['discounted_price'],
                    'discount_percentage' => $promotion['discount_percentage'],
                    'store' => $promotion['store'],
                    'category' => $promotion['category'],
                    'status' => 'pending',
                    'ai_generated_post' => $aiPost
                ]);
                
                $processed[] = [
                    'id' => $promotionModel->id,
                    'title' => $promotion['title'],
                    'original_url' => $promotion['source_url'],
                    'affiliate_url' => $affiliateUrl,
                    'url_changed' => $affiliateUrl !== $promotion['source_url'],
                    'ai_post_preview' => substr($aiPost ?: 'Post não gerado', 0, 150) . '...',
                    'discount_percentage' => $promotion['discount_percentage']
                ];
                
            } catch (\Exception $e) {
                $errors[] = [
                    'title' => $promotion['title'],
                    'error' => $e->getMessage()
                ];
            }
        }
        
        return response()->json([
            'success' => true,
            'deleted_for_test' => $deletedTitles,
            'total_scraped' => count($promotions),
            'processed' => $processed,
            'errors' => $errors,
            'summary' => [
                'deleted_count' => count($deletedTitles),
                'new_products_created' => count($processed),
                'failed' => count($errors),
                'affiliate_links_generated' => count(array_filter($processed, fn($p) => $p['url_changed']))
            ]
        ]);
        
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
    }
})->name('test.fresh-products');

// Rota de teste de links de afiliado (sem middleware para testes)
Route::get('/test-affiliate', function () {
    try {
        $amazonService = app(\App\Services\AmazonService::class);
        
        // URLs de teste
        $testUrls = [
            'https://www.amazon.com.br/dp/B08N5WRWNW',
            'https://www.amazon.com.br/Echo-Dot-5%C2%AA-gera%C3%A7%C3%A3o-Alexa/dp/B09B8V1LZ3'
        ];
        
        $results = [];
        foreach ($testUrls as $url) {
            $affiliateUrl = $amazonService->generateAffiliateLink($url);
            $results[] = [
                'original_url' => $url,
                'affiliate_url' => $affiliateUrl,
                'is_amazon' => str_contains($url, 'amazon'),
                'changed' => $url !== $affiliateUrl
            ];
        }
        
        return response()->json([
            'success' => true,
            'affiliate_tag' => config('services.amazon.affiliate_tag'),
            'test_results' => $results
        ]);
        
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
})->name('test.affiliate');

// Removi o middleware de autenticação para permitir testes
Route::prefix('promotions')->name('promotions.')->group(function () {
    Route::get('/', [PromotionController::class, 'index'])->name('index');
    Route::get('/{promotion}', [PromotionController::class, 'show'])->name('show');
    Route::delete('/{promotion}', [PromotionController::class, 'destroy'])->name('destroy');

    // Ações via AJAX
    Route::post('/scrape', [PromotionController::class, 'scrape'])->name('scrape');
    Route::post('/{promotion}/approve', [PromotionController::class, 'approve'])->name('approve');
    Route::post('/{promotion}/reject', [PromotionController::class, 'reject'])->name('reject');
    Route::post('/{promotion}/regenerate-post', [PromotionController::class, 'regeneratePost'])->name('regenerate-post');
    Route::put('/{promotion}/update-post', [PromotionController::class, 'updatePost'])->name('update-post');
    Route::post('/bulk-action', [PromotionController::class, 'bulkAction'])->name('bulk-action');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->name('dashboard');

Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

// Rota para testar com produtos completamente novos (deleta alguns e reprocessa)
Route::get('/test-fresh-products', function () {
    try {
        // Deletar as últimas 3 promoções para testar o fluxo completo
        $lastPromotions = \App\Models\Promotion::orderBy('created_at', 'desc')->limit(3)->get();
        $deletedTitles = [];
        
        foreach ($lastPromotions as $promo) {
            $deletedTitles[] = $promo->title;
            $promo->delete();
        }
        
        // Agora processar novamente
        $scrapper = app(\App\Services\PromobitScrapperService::class);
        $amazonService = app(\App\Services\AmazonService::class);
        $geminiService = app(\App\Services\GeminiService::class);
        
        $promotions = $scrapper->scrapePromotions('amazon', 3);
        
        if (empty($promotions)) {
            return response()->json([
                'success' => false,
                'error' => 'Nenhuma promoção encontrada'
            ]);
        }
        
        $processed = [];
        $errors = [];
        
        foreach ($promotions as $promotion) {
            try {
                // Gerar link de afiliado se for Amazon
                $affiliateUrl = null;
                if (!empty($promotion['source_url'])) {
                    $affiliateUrl = $amazonService->generateAffiliateLink($promotion['source_url']);
                }
                
                // Gerar post com IA
                $aiPost = null;
                try {
                    $aiPost = $geminiService->generatePromotionPost($promotion);
                } catch (\Exception $e) {
                    // Se falhar na IA, continua sem o post
                    $aiPost = "Post não pôde ser gerado: " . $e->getMessage();
                }
                
                // Criar promoção
                $promotionModel = \App\Models\Promotion::create([
                    'title' => $promotion['title'],
                    'description' => $promotion['description'],
                    'url' => $affiliateUrl ?: $promotion['source_url'],
                    'source_url' => $promotion['source_url'],
                    'image' => $promotion['image'],
                    'original_price' => $promotion['original_price'],
                    'discounted_price' => $promotion['discounted_price'],
                    'discount_percentage' => $promotion['discount_percentage'],
                    'store' => $promotion['store'],
                    'category' => $promotion['category'],
                    'status' => 'pending',
                    'ai_generated_post' => $aiPost
                ]);
                
                $processed[] = [
                    'id' => $promotionModel->id,
                    'title' => $promotion['title'],
                    'original_url' => $promotion['source_url'],
                    'affiliate_url' => $affiliateUrl,
                    'url_changed' => $affiliateUrl !== $promotion['source_url'],
                    'ai_post_preview' => substr($aiPost ?: 'Post não gerado', 0, 150) . '...',
                    'discount_percentage' => $promotion['discount_percentage']
                ];
                
            } catch (\Exception $e) {
                $errors[] = [
                    'title' => $promotion['title'],
                    'error' => $e->getMessage()
                ];
            }
        }
        
        return response()->json([
            'success' => true,
            'deleted_for_test' => $deletedTitles,
            'total_scraped' => count($promotions),
            'processed' => $processed,
            'errors' => $errors,
            'summary' => [
                'deleted_count' => count($deletedTitles),
                'new_products_created' => count($processed),
                'failed' => count($errors),
                'affiliate_links_generated' => count(array_filter($processed, fn($p) => $p['url_changed']))
            ]
        ]);
        
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
    }
})->name('test.fresh-products');

require __DIR__ . '/auth.php';
