<?php

namespace App\Http\Controllers;

use App\Models\Promotion;
use App\Services\PromotionProcessorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PromotionController extends Controller
{
    protected $promotionProcessor;

    public function __construct(PromotionProcessorService $promotionProcessor)
    {
        $this->promotionProcessor = $promotionProcessor;
    }

    public function index(Request $request)
    {
        $query = Promotion::query();

        // Filtros
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('store')) {
            $query->where('store', 'like', '%' . $request->store . '%');
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('title', 'like', '%' . $request->search . '%')
                  ->orWhere('description', 'like', '%' . $request->search . '%');
            });
        }

        $promotions = $query->orderBy('created_at', 'desc')->paginate(20);
        $stats = $this->promotionProcessor->getPromotionStats();

        return view('promotions.index', compact('promotions', 'stats'));
    }

    public function show(Promotion $promotion)
    {
        return view('promotions.show', compact('promotion'));
    }

    public function scrape(Request $request)
    {
        try {
            $store = $request->input('store');
            $limit = $request->input('limit', 10);

            $result = $this->promotionProcessor->processNewPromotions($store, $limit);

            if ($result['success']) {
                $message = "Processamento concluído! ";
                $message .= "Encontradas: {$result['stats']['total_found']}, ";
                $message .= "Processadas: {$result['stats']['successfully_processed']}, ";
                $message .= "Erros: {$result['stats']['failed']}";

                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'data' => $result
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'] ?? 'Erro no processamento'
                ], 500);
            }

        } catch (\Exception $e) {
            Log::error('Erro no scrapping via controller: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erro interno: ' . $e->getMessage()
            ], 500);
        }
    }

    public function approve(Request $request, Promotion $promotion)
    {
        try {
            $result = $this->promotionProcessor->approveAndSendPromotion($promotion);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => $result['message']
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $result['message']
                ], 500);
            }

        } catch (\Exception $e) {
            Log::error('Erro ao aprovar promoção: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erro interno: ' . $e->getMessage()
            ], 500);
        }
    }

    public function reject(Request $request, Promotion $promotion)
    {
        try {
            $promotion->update([
                'status' => 'rejected',
                'is_approved' => false
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Promoção rejeitada com sucesso!'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao rejeitar promoção: ' . $e->getMessage()
            ], 500);
        }
    }

    public function regeneratePost(Request $request, Promotion $promotion)
    {
        try {
            $result = $this->promotionProcessor->regeneratePost($promotion);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Post regenerado com sucesso!',
                    'post' => $result['post']
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $result['message']
                ], 500);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro interno: ' . $e->getMessage()
            ], 500);
        }
    }

    public function generateVariations(Request $request, Promotion $promotion)
    {
        try {
            $count = $request->input('count', 3);
            $result = $this->promotionProcessor->generatePostVariations($promotion, $count);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'variations' => $result['variations'],
                    'original' => $result['original']
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $result['message']
                ], 500);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro interno: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Promotion $promotion)
    {
        try {
            $promotion->delete();

            return response()->json([
                'success' => true,
                'message' => 'Promoção excluída com sucesso!'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao excluir promoção: ' . $e->getMessage()
            ], 500);
        }
    }

    public function updatePost(Request $request, Promotion $promotion)
    {
        try {
            $request->validate([
                'gemini_generated_post' => 'required|string|max:4000'
            ]);

            $promotion->update([
                'gemini_generated_post' => $request->gemini_generated_post,
                'status' => 'pending'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Post atualizado com sucesso!'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao atualizar post: ' . $e->getMessage()
            ], 500);
        }
    }

    public function bulkAction(Request $request)
    {
        try {
            $request->validate([
                'action' => 'required|in:approve,reject,delete',
                'promotions' => 'required|array',
                'promotions.*' => 'exists:promotions,id'
            ]);

            $promotions = Promotion::whereIn('id', $request->promotions)->get();
            $results = [];

            foreach ($promotions as $promotion) {
                switch ($request->action) {
                    case 'approve':
                        $result = $this->promotionProcessor->approveAndSendPromotion($promotion);
                        break;
                    case 'reject':
                        $promotion->update(['status' => 'rejected', 'is_approved' => false]);
                        $result = ['success' => true];
                        break;
                    case 'delete':
                        $promotion->delete();
                        $result = ['success' => true];
                        break;
                }
                $results[] = $result;
            }

            $successCount = collect($results)->where('success', true)->count();
            $totalCount = count($results);

            return response()->json([
                'success' => true,
                'message' => "Ação executada em {$successCount}/{$totalCount} promoções"
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro na ação em lote: ' . $e->getMessage()
            ], 500);
        }
    }
}
