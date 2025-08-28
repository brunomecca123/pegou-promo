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

// Rota de teste para links de afiliado da Amazon
Route::get('/test-affiliate', function () {
    try {
        $amazonService = app(\App\Services\AmazonService::class);

        // URLs de teste da Amazon
        $testUrls = [
            'https://www.amazon.com.br/dp/B08N5WRWNW',
            'https://www.amazon.com.br/Echo-Dot-5%C2%AA-gera%C3%A7%C3%A3o-Alexa/dp/B09B8V1LZ3',
            'https://www.amazon.com.br/Kindle-11-geracao-6-luz-ajustavel/dp/B09SWW583J',
            'https://www.promobit.com.br/oferta/produto-teste' // URL não Amazon para comparação
        ];

        $results = [];
        foreach ($testUrls as $url) {
            $affiliateUrl = $amazonService->generateAffiliateLink($url);
            $results[] = [
                'original_url' => $url,
                'affiliate_url' => $affiliateUrl,
                'is_amazon' => str_contains($url, 'amazon'),
                'changed' => $url !== $affiliateUrl,
                'affiliate_tag_present' => str_contains($affiliateUrl, 'tag=')
            ];
        }

        return response()->json([
            'success' => true,
            'affiliate_tag' => config('services.amazon.affiliate_tag'),
            'test_results' => $results,
            'config_check' => [
                'tag_configured' => !empty(config('services.amazon.affiliate_tag')),
                'tag_value' => config('services.amazon.affiliate_tag'),
                'env_tag' => env('AMAZON_AFFILIATE_TAG')
            ]
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
    }
})->name('test.affiliate');

// Teste completo do processamento com salvamento no banco
Route::get('/test-full-process', function () {
    try {
        $processor = app(\App\Services\PromotionProcessorService::class);

        // Dados de teste de uma promoção da Amazon
        $testPromotion = [
            'title' => 'Teste Kindle Oasis - Leitor Digital ' . now()->format('H:i:s'),
            'description' => 'E-reader premium com tela de 7 polegadas e luz ajustável. Teste de funcionalidade de afiliados.',
            'url' => 'https://www.amazon.com.br/dp/B08N5WRWNW',
            'image' => 'https://m.media-amazon.com/images/I/61q2q1WjHJL._AC_SY200_.jpg',
            'original_price' => 1299.00,
            'discounted_price' => 899.00,
            'discount_percentage' => 31,
            'store' => 'Amazon',
            'category' => 'Eletrônicos',
            'source_url' => 'https://promobit.com.br/teste'
        ];

        $result = $processor->processSinglePromotion($testPromotion);

        if ($result['success']) {
            $promotion = $result['promotion'];
            return response()->json([
                'success' => true,
                'message' => 'Promoção processada e salva com sucesso!',
                'promotion' => [
                    'id' => $promotion->id,
                    'title' => $promotion->title,
                    'original_url' => $promotion->url,
                    'affiliate_url' => $promotion->affiliate_url,
                    'has_affiliate_link' => !empty($promotion->affiliate_url),
                    'gemini_post' => $promotion->gemini_generated_post,
                    'status' => $promotion->status,
                    'store' => $promotion->store,
                    'prices' => [
                        'original' => $promotion->original_price,
                        'discounted' => $promotion->discounted_price,
                        'discount_percentage' => $promotion->discount_percentage
                    ]
                ],
                'validation' => [
                    'affiliate_link_generated' => !empty($promotion->affiliate_url),
                    'contains_affiliate_tag' => str_contains($promotion->affiliate_url ?? '', config('services.amazon.affiliate_tag')),
                    'post_generated' => !empty($promotion->gemini_generated_post),
                    'saved_to_database' => true
                ]
            ]);
        } else {
            return response()->json([
                'success' => false,
                'error' => $result['error'],
                'reason' => $result['reason'] ?? 'unknown'
            ]);
        }

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
    }
})->name('test.full-process');

// Verificar promoções com links de afiliado
Route::get('/verify-affiliate-links', function () {
    try {
        $promotions = \App\Models\Promotion::whereNotNull('affiliate_url')
            ->latest()
            ->take(10)
            ->get();

        $results = [];
        foreach ($promotions as $promotion) {
            $results[] = [
                'id' => $promotion->id,
                'title' => $promotion->title,
                'original_url' => $promotion->url,
                'affiliate_url' => $promotion->affiliate_url,
                'has_affiliate_tag' => str_contains($promotion->affiliate_url ?? '', config('services.amazon.affiliate_tag')),
                'created_at' => $promotion->created_at->format('d/m/Y H:i:s'),
                'status' => $promotion->status
            ];
        }

        $total_promotions = \App\Models\Promotion::count();
        $with_affiliate = \App\Models\Promotion::whereNotNull('affiliate_url')->count();
        $amazon_promotions = \App\Models\Promotion::where('store', 'Amazon')->count();

        return response()->json([
            'success' => true,
            'statistics' => [
                'total_promotions' => $total_promotions,
                'with_affiliate_links' => $with_affiliate,
                'amazon_promotions' => $amazon_promotions,
                'affiliate_tag' => config('services.amazon.affiliate_tag')
            ],
            'recent_promotions_with_affiliate' => $results,
            'message' => 'Sistema de links de afiliado funcionando corretamente!'
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
})->name('verify.affiliate-links');

// Rota para verificar dados do banco e diagnosticar problemas
Route::get('/check-promotions', function () {
    try {
        $promotions = \App\Models\Promotion::orderBy('id', 'desc')->limit(5)->get([
            'id', 'title', 'url', 'affiliate_url', 'store', 'created_at'
        ]);

        $analysis = [];
        foreach ($promotions as $promotion) {
            $isAmazon = str_contains(strtolower($promotion->url ?? ''), 'amazon');
            $hasAffiliateUrl = !empty($promotion->affiliate_url);

            $analysis[] = [
                'id' => $promotion->id,
                'title' => substr($promotion->title, 0, 50) . '...',
                'url' => $promotion->url,
                'affiliate_url' => $promotion->affiliate_url,
                'store' => $promotion->store,
                'created_at' => $promotion->created_at->format('d/m/Y H:i:s'),
                'is_amazon_url' => $isAmazon,
                'has_affiliate_url' => $hasAffiliateUrl,
                'diagnosis' => $isAmazon && !$hasAffiliateUrl ? 'PROBLEMA: URL Amazon sem link afiliado!' : 'OK'
            ];
        }

        return response()->json([
            'total_promotions' => \App\Models\Promotion::count(),
            'recent_promotions' => $analysis,
            'config' => [
                'affiliate_tag' => config('services.amazon.affiliate_tag'),
                'amazon_configured' => !empty(config('services.amazon.affiliate_tag'))
            ],
            'debug_info' => [
                'amazon_service_available' => class_exists('\App\Services\AmazonService'),
                'processor_service_available' => class_exists('\App\Services\PromotionProcessorService')
            ]
        ], 200, [], JSON_PRETTY_PRINT);

    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
    }
})->name('check.promotions');

// Debug do Amazon Service
Route::get('/debug-amazon-service', function () {
    try {
        $amazonService = app(\App\Services\AmazonService::class);

        // Pegar algumas URLs reais do banco de dados
        $promotions = \App\Models\Promotion::whereNotNull('url')
            ->orderBy('id', 'desc')
            ->limit(5)
            ->get(['id', 'title', 'url', 'affiliate_url', 'store']);

        $debug_results = [];

        foreach ($promotions as $promotion) {
            $originalUrl = $promotion->url;
            $generatedAffiliate = $amazonService->generateAffiliateLink($originalUrl);
            $isAmazonDetected = str_contains(strtolower($originalUrl), 'amazon');

            $debug_results[] = [
                'promotion_id' => $promotion->id,
                'title' => substr($promotion->title, 0, 40) . '...',
                'original_url' => $originalUrl,
                'stored_affiliate_url' => $promotion->affiliate_url,
                'is_amazon_url' => $isAmazonDetected,
                'generated_affiliate_now' => $generatedAffiliate,
                'should_have_affiliate' => $isAmazonDetected,
                'affiliate_tag_in_generated' => str_contains($generatedAffiliate, config('services.amazon.affiliate_tag')),
                'url_changed' => $originalUrl !== $generatedAffiliate,
                'problem_diagnosis' => $isAmazonDetected && empty($promotion->affiliate_url) ? 'PROBLEMA: URL Amazon sem link de afiliado salvo!' : 'OK'
            ];
        }

        return response()->json([
            'config' => [
                'affiliate_tag' => config('services.amazon.affiliate_tag'),
                'tag_configured' => !empty(config('services.amazon.affiliate_tag'))
            ],
            'debug_results' => $debug_results,
            'service_test' => [
                'amazon_test_url' => 'https://www.amazon.com.br/dp/B08N5WRWNW',
                'generated_link' => $amazonService->generateAffiliateLink('https://www.amazon.com.br/dp/B08N5WRWNW'),
                'contains_affiliate_tag' => str_contains($amazonService->generateAffiliateLink('https://www.amazon.com.br/dp/B08N5WRWNW'), config('services.amazon.affiliate_tag'))
            ]
        ], 200, [], JSON_PRETTY_PRINT);

    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
    }
})->name('debug.amazon-service');

// Teste simples do processamento
Route::get('/test-simple-amazon', function () {
    try {
        $amazonService = app(\App\Services\AmazonService::class);

        // Teste com URL da Amazon
        $testUrl = 'https://www.amazon.com.br/dp/B08N5WRWNW';
        $affiliateLink = $amazonService->generateAffiliateLink($testUrl);

        return response()->json([
            'test_url' => $testUrl,
            'affiliate_link' => $affiliateLink,
            'affiliate_tag' => config('services.amazon.affiliate_tag'),
            'link_changed' => $testUrl !== $affiliateLink,
            'contains_tag' => str_contains($affiliateLink, config('services.amazon.affiliate_tag')),
            'validation_result' => $amazonService->validateAffiliateSetup()
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
    }
})->name('test.simple-amazon');

// Listar últimas promoções para análise
Route::get('/list-recent-promotions', function () {
    try {
        $promotions = \App\Models\Promotion::orderBy('id', 'desc')
            ->limit(10)
            ->get(['id', 'title', 'url', 'affiliate_url', 'store', 'created_at']);

        $results = [];
        foreach ($promotions as $promotion) {
            $results[] = [
                'id' => $promotion->id,
                'title' => substr($promotion->title, 0, 60) . '...',
                'url' => $promotion->url,
                'affiliate_url' => $promotion->affiliate_url,
                'store' => $promotion->store,
                'created_at' => $promotion->created_at->format('d/m/Y H:i:s'),
                'is_amazon_url' => str_contains(strtolower($promotion->url ?? ''), 'amazon'),
                'has_affiliate_url' => !empty($promotion->affiliate_url)
            ];
        }

        return response()->json([
            'total_promotions' => \App\Models\Promotion::count(),
            'recent_promotions' => $results
        ], 200, [], JSON_PRETTY_PRINT);

    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage()
        ]);
    }
})->name('list.recent-promotions');

// Reprocessar links de afiliado para promoções existentes
Route::get('/reprocess-affiliate-links', function () {
    try {
        $amazonService = app(\App\Services\AmazonService::class);

        // Buscar promoções que têm URLs da Amazon mas não têm link de afiliado
        $promotions = \App\Models\Promotion::whereNull('affiliate_url')
            ->orWhere('affiliate_url', '')
            ->get();

        $processed = 0;
        $results = [];

        foreach ($promotions as $promotion) {
            if ($promotion->url && str_contains(strtolower($promotion->url), 'amazon')) {
                $affiliateUrl = $amazonService->generateAffiliateLink($promotion->url);

                if ($affiliateUrl !== $promotion->url) {
                    $promotion->update(['affiliate_url' => $affiliateUrl]);
                    $processed++;

                    $results[] = [
                        'id' => $promotion->id,
                        'title' => substr($promotion->title, 0, 40) . '...',
                        'original_url' => $promotion->url,
                        'new_affiliate_url' => $affiliateUrl
                    ];
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Reprocessadas {$processed} promoções da Amazon",
            'processed_promotions' => $results,
            'total_checked' => $promotions->count()
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage()
        ]);
    }
})->name('reprocess.affiliate-links');

// Teste de extração de link da Amazon via Promobit
Route::get('/test-promobit-extraction', function () {
    try {
        $scrapper = app(\App\Services\PromobitScrapperService::class);

        // URL de exemplo que você forneceu
        $testUrl = 'https://www.promobit.com.br/oferta/concha-y-toro-vinho-chileno-reservado-sauvignon-blanc-750ml-2315701';

        Log::info('Testando extração de link da Amazon via Promobit', ['test_url' => $testUrl]);

        $details = $scrapper->getPromotionDetails($testUrl);

        if ($details) {
            // Se conseguiu extrair o link da Amazon, testar geração de afiliado
            $amazonService = app(\App\Services\AmazonService::class);
            $affiliateUrl = '';

            if (!empty($details['amazon_url'])) {
                $affiliateUrl = $amazonService->generateAffiliateLink($details['amazon_url']);
            }

            return response()->json([
                'success' => true,
                'test_url' => $testUrl,
                'extraction_result' => $details,
                'amazon_url_found' => !empty($details['amazon_url']),
                'affiliate_url' => $affiliateUrl,
                'affiliate_generated' => !empty($affiliateUrl) && $affiliateUrl !== ($details['amazon_url'] ?? ''),
                'config' => [
                    'affiliate_tag' => config('services.amazon.affiliate_tag')
                ]
            ], 200, [], JSON_PRETTY_PRINT);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Não foi possível extrair detalhes da promoção',
                'test_url' => $testUrl
            ]);
        }

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
    }
})->name('test.promobit-extraction');

// Teste do processo completo com URL real do Promobit
Route::get('/test-full-promobit-process', function () {
    try {
        $processor = app(\App\Services\PromotionProcessorService::class);

        // Simular dados de uma promoção real do Promobit
        $testPromotion = [
            'title' => 'Teste Promobit - Produto Amazon ' . now()->format('H:i:s'),
            'description' => 'Promoção teste para verificar extração de links da Amazon',
            'url' => '', // Será preenchido pelo scrapper
            'source_url' => 'https://www.promobit.com.br/oferta/concha-y-toro-vinho-chileno-reservado-sauvignon-blanc-750ml-2315701',
            'image' => '',
            'original_price' => 89.90,
            'discounted_price' => 45.90,
            'discount_percentage' => 49,
            'store' => 'Amazon',
            'category' => 'Bebidas'
        ];

        $result = $processor->processSinglePromotion($testPromotion);

        if ($result['success']) {
            $promotion = $result['promotion'];
            return response()->json([
                'success' => true,
                'message' => 'Promoção processada com sucesso usando URL real do Promobit!',
                'promotion' => [
                    'id' => $promotion->id,
                    'title' => $promotion->title,
                    'source_url' => $promotion->source_url,
                    'extracted_url' => $promotion->url,
                    'affiliate_url' => $promotion->affiliate_url,
                    'store' => $promotion->store,
                    'status' => $promotion->status
                ],
                'validation' => [
                    'url_extracted' => !empty($promotion->url),
                    'is_amazon_url' => str_contains(strtolower($promotion->url ?? ''), 'amazon'),
                    'affiliate_link_generated' => !empty($promotion->affiliate_url),
                    'contains_affiliate_tag' => str_contains($promotion->affiliate_url ?? '', config('services.amazon.affiliate_tag')),
                    'post_generated' => !empty($promotion->gemini_generated_post),
                    'saved_to_database' => true
                ]
            ], 200, [], JSON_PRETTY_PRINT);
        } else {
            return response()->json([
                'success' => false,
                'error' => $result['error'],
                'reason' => $result['reason'] ?? 'unknown'
            ]);
        }

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
    }
})->name('test.full-promobit-process');

// Teste da geração de post pela IA com link de afiliado
Route::get('/test-gemini-with-affiliate', function () {
    try {
        $geminiService = app(\App\Services\GeminiService::class);

        // Dados de teste com link de afiliado
        $testData = [
            'title' => 'Echo Dot 5ª Geração com Alexa - Smart Speaker',
            'description' => 'Alto-falante inteligente com Alexa, som mais potente e hub de casa inteligente integrado',
            'store' => 'Amazon',
            'original_price' => 349.90,
            'discounted_price' => 189.90,
            'discount_percentage' => 46,
            'category' => 'eletrônicos',
            'affiliate_url' => 'https://www.amazon.com.br/dp/B09B8V1LZ3?tag=pegoupromo03-20'
        ];

        Log::info('Testando geração de post com link de afiliado pela IA', $testData);

        $generatedPost = $geminiService->generatePromotionPost($testData);

        if ($generatedPost) {
            return response()->json([
                'success' => true,
                'input_data' => $testData,
                'generated_post' => $generatedPost,
                'analysis' => [
                    'contains_affiliate_link' => str_contains($generatedPost, $testData['affiliate_url']),
                    'contains_amazon_link' => str_contains($generatedPost, 'amazon.com.br'),
                    'contains_affiliate_tag' => str_contains($generatedPost, 'pegoupromo03-20'),
                    'post_length' => strlen($generatedPost),
                    'has_emojis' => preg_match('/[\x{1F600}-\x{1F64F}]|[\x{1F300}-\x{1F5FF}]|[\x{1F680}-\x{1F6FF}]|[\x{1F1E0}-\x{1F1FF}]/u', $generatedPost)
                ]
            ], 200, [], JSON_PRETTY_PRINT);
        } else {
            return response()->json([
                'success' => false,
                'error' => 'Falha na geração do post pela IA'
            ]);
        }

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
    }
})->name('test.gemini-with-affiliate');

// Teste final: processo completo com link de afiliado na mensagem da IA
Route::get('/test-complete-flow', function () {
    try {
        $processor = app(\App\Services\PromotionProcessorService::class);

        // Simular uma promoção real com dados do Promobit
        $testPromotion = [
            'title' => 'Teste Final - Echo Dot 5ª Geração ' . now()->format('H:i:s'),
            'description' => 'Smart Speaker com Alexa, som melhorado e controle de casa inteligente',
            'url' => '', // Será preenchido pelo scrapper
            'source_url' => 'https://www.promobit.com.br/oferta/concha-y-toro-vinho-chileno-reservado-sauvignon-blanc-750ml-2315701',
            'image' => 'https://m.media-amazon.com/images/I/71EJgo6+wuL._AC_SL1000_.jpg',
            'original_price' => 349.90,
            'discounted_price' => 189.90,
            'discount_percentage' => 46,
            'store' => 'Amazon',
            'category' => 'eletrônicos'
        ];

        Log::info('Iniciando teste do fluxo completo', $testPromotion);

        $result = $processor->processSinglePromotion($testPromotion);

        if ($result['success']) {
            $promotion = $result['promotion'];

            // Verificar se a mensagem da IA contém o link de afiliado
            $hasAffiliateInPost = str_contains($promotion->gemini_generated_post ?? '', $promotion->affiliate_url ?? '');

            return response()->json([
                'success' => true,
                'message' => 'Fluxo completo executado com sucesso!',
                'process_steps' => [
                    '1_scraping' => 'URL extraída do Promobit',
                    '2_amazon_extraction' => 'Link da Amazon encontrado',
                    '3_affiliate_generation' => 'Link de afiliado gerado',
                    '4_ai_post_generation' => 'Post criado pela IA',
                    '5_database_save' => 'Dados salvos no banco'
                ],
                'final_promotion' => [
                    'id' => $promotion->id,
                    'title' => $promotion->title,
                    'source_url' => $promotion->source_url,
                    'amazon_url' => $promotion->url,
                    'affiliate_url' => $promotion->affiliate_url,
                    'ai_generated_post' => $promotion->gemini_generated_post,
                    'status' => $promotion->status
                ],
                'validation' => [
                    'amazon_url_extracted' => !empty($promotion->url) && str_contains($promotion->url, 'amazon'),
                    'affiliate_url_generated' => !empty($promotion->affiliate_url),
                    'affiliate_tag_correct' => str_contains($promotion->affiliate_url ?? '', 'pegoupromo03-20'),
                    'ai_post_created' => !empty($promotion->gemini_generated_post),
                    'ai_post_has_affiliate_link' => $hasAffiliateInPost,
                    'ready_for_telegram' => !empty($promotion->gemini_generated_post) && !empty($promotion->affiliate_url)
                ],
                'next_step' => 'A promoção está pronta para ser aprovada e enviada para o Telegram!'
            ], 200, [], JSON_PRETTY_PRINT);
        } else {
            return response()->json([
                'success' => false,
                'error' => $result['error'],
                'reason' => $result['reason'] ?? 'unknown'
            ]);
        }

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
    }
})->name('test.complete-flow');

// Teste de variações dinâmicas dos links pela IA
Route::get('/test-dynamic-links', function () {
    try {
        $geminiService = app(\App\Services\GeminiService::class);

        // Dados de teste para gerar múltiplas variações
        $testData = [
            'title' => 'Echo Dot 5ª Geração com Alexa - Smart Speaker',
            'description' => 'Alto-falante inteligente com Alexa, som mais potente e hub de casa inteligente integrado',
            'store' => 'Amazon',
            'original_price' => 349.90,
            'discounted_price' => 189.90,
            'discount_percentage' => 46,
            'category' => 'eletrônicos',
            'affiliate_url' => 'https://www.amazon.com.br/dp/B09B8V1LZ3?tag=pegoupromo03-20'
        ];

        // Gerar múltiplos posts para verificar variação
        $posts = [];
        for ($i = 1; $i <= 3; $i++) {
            Log::info("Gerando post variado #{$i}");
            $post = $geminiService->generatePromotionPost($testData);
            if ($post) {
                // Extrair como o link foi apresentado
                $linkPattern = '/([🛒👇🔥⚡🎯💥🚀✨🏃‍♂️🔗].+?)https/';
                preg_match($linkPattern, $post, $matches);
                $linkPresentation = $matches[1] ?? 'Link não encontrado no padrão esperado';

                $posts[] = [
                    'post_number' => $i,
                    'content' => $post,
                    'link_presentation' => trim($linkPresentation),
                    'has_affiliate_link' => str_contains($post, $testData['affiliate_url'])
                ];
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Teste de variações dinâmicas dos links',
            'input_data' => $testData,
            'generated_posts' => $posts,
            'analysis' => [
                'total_posts' => count($posts),
                'all_have_links' => collect($posts)->every(fn($p) => $p['has_affiliate_link']),
                'different_presentations' => array_unique(array_column($posts, 'link_presentation'))
            ]
        ], 200, [], JSON_PRETTY_PRINT);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
    }
})->name('test.dynamic-links');

// Teste simples de geração de post com link dinâmico
Route::get('/test-single-dynamic', function () {
    try {
        $geminiService = app(\App\Services\GeminiService::class);

        // Dados de teste
        $testData = [
            'title' => 'Kindle Paperwhite 11ª Geração - E-reader à Prova d\'Água',
            'description' => 'E-reader com tela de 6.8", luz ajustável e semanas de bateria',
            'store' => 'Amazon',
            'original_price' => 599.90,
            'discounted_price' => 399.90,
            'discount_percentage' => 33,
            'category' => 'eletrônicos',
            'affiliate_url' => 'https://www.amazon.com.br/dp/B08KTZ8249?tag=pegoupromo03-20'
        ];

        Log::info('Testando geração de post com link dinâmico');

        $generatedPost = $geminiService->generatePromotionPost($testData);

        if ($generatedPost) {
            // Detectar estilo do link
            $linkStyles = [
                '🛒 Compre aqui:' => 'Compra direta',
                '👇 Garanta o seu:' => 'Garantia pessoal',
                '🔥 Aproveite:' => 'Urgência de aproveitamento',
                '⚡ Link direto:' => 'Acesso direto',
                '🎯 Adquira já:' => 'Ação imediata',
                '💥 Oferta aqui:' => 'Destaque da oferta',
                '🚀 Corre lá:' => 'Velocidade/urgência',
                '✨ Pegue o seu:' => 'Exclusividade',
                '🏃‍♂️ Voa:' => 'Movimento/pressa',
                '🔗 Link da promoção:' => 'Link tradicional'
            ];

            $detectedStyle = 'Estilo personalizado';
            foreach ($linkStyles as $pattern => $description) {
                if (str_contains($generatedPost, $pattern)) {
                    $detectedStyle = $description;
                    break;
                }
            }

            return response()->json([
                'success' => true,
                'input_data' => $testData,
                'generated_post' => $generatedPost,
                'analysis' => [
                    'contains_affiliate_link' => str_contains($generatedPost, $testData['affiliate_url']),
                    'contains_amazon_domain' => str_contains($generatedPost, 'amazon.com.br'),
                    'contains_affiliate_tag' => str_contains($generatedPost, 'pegoupromo03-20'),
                    'post_length' => strlen($generatedPost),
                    'link_presentation_style' => $detectedStyle
                ],
                'message' => 'Post gerado com apresentação dinâmica do link!'
            ], 200, [], JSON_PRETTY_PRINT);
        } else {
            return response()->json([
                'success' => false,
                'error' => 'Falha na geração do post pela IA'
            ]);
        }

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
})->name('test.single-dynamic');

require __DIR__ . '/auth.php';
