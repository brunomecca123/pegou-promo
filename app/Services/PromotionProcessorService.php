<?php

namespace App\Services;

use App\Models\Promotion;
use Illuminate\Support\Facades\Log;

class PromotionProcessorService
{
    protected $promobitScrapper;
    protected $geminiService;
    protected $amazonService;
    protected $telegramService;

    public function __construct(
        PromobitScrapperService $promobitScrapper,
        GeminiService $geminiService,
        AmazonService $amazonService,
        TelegramService $telegramService
    ) {
        $this->promobitScrapper = $promobitScrapper;
        $this->geminiService = $geminiService;
        $this->amazonService = $amazonService;
        $this->telegramService = $telegramService;
    }

    public function processNewPromotions($store = null, $limit = 10)
    {
        try {
            Log::info('Iniciando processamento de novas promoções', [
                'store' => $store,
                'limit' => $limit
            ]);

            // 1. Fazer scrapping das promoções
            $scrapedPromotions = $this->promobitScrapper->scrapePromotions($store, $limit);

            if (empty($scrapedPromotions)) {
                Log::warning('Nenhuma promoção encontrada no scrapping');
                return ['success' => false, 'message' => 'Nenhuma promoção encontrada'];
            }

            $processed = [];
            $errors = [];
            $skipped = []; // Para duplicatas

            foreach ($scrapedPromotions as $promotionData) {
                try {
                    $result = $this->processSinglePromotion($promotionData);
                    if ($result['success']) {
                        $processed[] = $result['promotion'];
                    } else {
                        if (isset($result['reason']) && $result['reason'] === 'duplicate') {
                            $skipped[] = $result['error'];
                        } else {
                            $errors[] = $result['error'];
                        }
                    }
                } catch (\Exception $e) {
                    $errors[] = "Erro ao processar promoção '{$promotionData['title']}': " . $e->getMessage();
                    Log::error('Erro no processamento individual', [
                        'promotion' => $promotionData['title'],
                        'error' => $e->getMessage()
                    ]);
                }
            }

            Log::info('Processamento concluído', [
                'total_found' => count($scrapedPromotions),
                'processed' => count($processed),
                'skipped_duplicates' => count($skipped),
                'errors' => count($errors)
            ]);

            return [
                'success' => true,
                'processed' => $processed,
                'errors' => $errors,
                'skipped' => $skipped,
                'stats' => [
                    'total_found' => count($scrapedPromotions),
                    'successfully_processed' => count($processed),
                    'skipped_duplicates' => count($skipped),
                    'failed' => count($errors)
                ]
            ];

        } catch (\Exception $e) {
            Log::error('Erro geral no processamento de promoções: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Erro geral: ' . $e->getMessage()
            ];
        }
    }

    public function processSinglePromotion(array $promotionData)
    {
        try {
            // Verificar se já existe uma promoção com o mesmo título
            $existingPromotion = Promotion::where('title', $promotionData['title'])->first();

            if ($existingPromotion) {
                Log::info('Promoção já existe, pulando processamento', [
                    'title' => $promotionData['title'],
                    'existing_id' => $existingPromotion->id,
                    'existing_created_at' => $existingPromotion->created_at
                ]);

                return [
                    'success' => false,
                    'error' => "Produto '{$promotionData['title']}' já foi postado anteriormente (ID: {$existingPromotion->id})",
                    'reason' => 'duplicate'
                ];
            }

            // 2. Obter detalhes completos da promoção
            if (!empty($promotionData['source_url'])) {
                $details = $this->promobitScrapper->getPromotionDetails($promotionData['source_url']);
                if ($details && !empty($details['direct_url'])) {
                    $promotionData['url'] = $details['direct_url'];
                    if (!empty($details['full_description'])) {
                        $promotionData['description'] = $details['full_description'];
                    }
                }
            }

            // 3. Gerar link de afiliado (se for Amazon)
            if (!empty($promotionData['url'])) {
                $promotionData['affiliate_url'] = $this->amazonService->generateAffiliateLink($promotionData['url']);
            }

            // 4. Gerar post com IA
            $generatedPost = $this->geminiService->generatePromotionPost($promotionData);
            if ($generatedPost) {
                $promotionData['gemini_generated_post'] = $generatedPost;
                $promotionData['status'] = 'pending'; // Aguardando aprovação
            } else {
                $promotionData['status'] = 'draft'; // Falhou na geração
                Log::warning('Falha na geração do post para: ' . $promotionData['title']);
            }

            // 5. Salvar no banco
            $promotion = Promotion::create($promotionData);

            return [
                'success' => true,
                'promotion' => $promotion
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    public function approveAndSendPromotion(Promotion $promotion)
    {
        try {
            // Marcar como aprovada
            $promotion->update([
                'is_approved' => true,
                'status' => 'approved'
            ]);

            // Enviar para o Telegram
            $sent = $this->telegramService->sendPromotion($promotion);

            if ($sent) {
                $promotion->update([
                    'status' => 'posted',
                    'posted_at' => now()
                ]);

                Log::info('Promoção enviada com sucesso', [
                    'promotion_id' => $promotion->id,
                    'title' => $promotion->title
                ]);

                return ['success' => true, 'message' => 'Promoção enviada com sucesso!'];
            } else {
                Log::error('Falha no envio da promoção para Telegram', [
                    'promotion_id' => $promotion->id
                ]);

                return ['success' => false, 'message' => 'Falha no envio para o Telegram'];
            }

        } catch (\Exception $e) {
            Log::error('Erro ao aprovar e enviar promoção: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Erro: ' . $e->getMessage()];
        }
    }

    public function regeneratePost(Promotion $promotion)
    {
        try {
            $promotionData = $promotion->toArray();

            $newPost = $this->geminiService->generatePromotionPost($promotionData);

            if ($newPost) {
                $promotion->update([
                    'gemini_generated_post' => $newPost,
                    'status' => 'pending'
                ]);

                return ['success' => true, 'post' => $newPost];
            } else {
                return ['success' => false, 'message' => 'Falha na regeneração do post'];
            }

        } catch (\Exception $e) {
            Log::error('Erro ao regenerar post: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Erro: ' . $e->getMessage()];
        }
    }

    public function generatePostVariations(Promotion $promotion, int $count = 3)
    {
        try {
            if (empty($promotion->gemini_generated_post)) {
                return ['success' => false, 'message' => 'Promoção não possui post gerado'];
            }

            $variations = $this->geminiService->generateVariations($promotion->gemini_generated_post, $count);

            return [
                'success' => true,
                'variations' => $variations,
                'original' => $promotion->gemini_generated_post
            ];

        } catch (\Exception $e) {
            Log::error('Erro ao gerar variações: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Erro: ' . $e->getMessage()];
        }
    }

    public function getPromotionStats()
    {
        try {
            $stats = [
                'total' => Promotion::count(),
                'pending' => Promotion::where('status', 'pending')->count(),
                'approved' => Promotion::where('status', 'approved')->count(),
                'posted' => Promotion::where('status', 'posted')->count(),
                'draft' => Promotion::where('status', 'draft')->count(),
                'today' => Promotion::whereDate('created_at', today())->count(),
                'this_week' => Promotion::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
                'this_month' => Promotion::whereMonth('created_at', now()->month)->count()
            ];

            return $stats;

        } catch (\Exception $e) {
            Log::error('Erro ao obter estatísticas: ' . $e->getMessage());
            return [];
        }
    }

    public function cleanupOldPromotions($daysOld = 30)
    {
        try {
            $cutoffDate = now()->subDays($daysOld);

            $deleted = Promotion::where('created_at', '<', $cutoffDate)
                ->where('status', '!=', 'posted')
                ->delete();

            Log::info("Limpeza concluída: {$deleted} promoções antigas removidas");

            return ['success' => true, 'deleted' => $deleted];

        } catch (\Exception $e) {
            Log::error('Erro na limpeza: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Erro: ' . $e->getMessage()];
        }
    }
}
