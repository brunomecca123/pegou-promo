<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Promotion;
use App\Services\TelegramService;
use App\Models\ErrorLog;

class PromotionController extends Controller
{
    public function store(Request $request)
    {
        try {
            $data = $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
                'url' => 'nullable|url',
                'image' => 'nullable|url',
            ]);

            $promotion = Promotion::create($data);
            if (!$promotion) {
                throw new \Exception('Erro ao cadastrar promoção.');
            }

            $telegramSent = app(TelegramService::class)->sendPromotion($promotion);
            if (!$telegramSent) {
                $promotion->delete();
                throw new \Exception('Erro ao enviar promoção para o Telegram. Nenhuma promoção foi cadastrada.');
            }

            return response()->json(['message' => 'Promoção cadastrada com sucesso!', 'promotion' => $promotion], 201);
        } catch (\Throwable $e) {
            ErrorLog::create([
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'class' => __CLASS__,
                'function' => __FUNCTION__,
            ]);
            return response()->json([
                'message' => 'Ocorreu um erro ao processar a promoção.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
