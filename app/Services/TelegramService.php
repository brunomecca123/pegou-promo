<?php

namespace App\Services;

use App\Models\Promotion;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramService
{
    protected $token;
    protected $channelId;

    public function __construct()
    {
        $this->token = config('services.telegram.bot_token', env('TELEGRAM_BOT_TOKEN'));
        $this->channelId = config('services.telegram.channel_id', env('TELEGRAM_CHANNEL_ID'));
    }

    public function sendPromotion(Promotion $promotion)
    {
        try {
            // Se tem post gerado pela IA, usar ele
            if (!empty($promotion->gemini_generated_post)) {
                $text = $this->formatAIGeneratedPost($promotion);

                // Verificar se o post da IA já contém um link
                $hasLinkInPost = str_contains($text, 'http') || str_contains($text, 'amazon.com');

                // Se não tem link no post da IA, adicionar link de afiliado
                if (!$hasLinkInPost) {
                    if (!empty($promotion->affiliate_url)) {
                        $text .= "\n\n🔗 <a href='{$promotion->affiliate_url}'>COMPRAR AGORA</a>";
                    } elseif (!empty($promotion->url)) {
                        $text .= "\n\n🔗 <a href='{$promotion->url}'>VER PROMOÇÃO</a>";
                    }
                }
            } else {
                // Fallback para formato manual
                $text = $this->formatManualPost($promotion);

                // Sempre adicionar link no formato manual
                if (!empty($promotion->affiliate_url)) {
                    $text .= "\n\n🔗 <a href='{$promotion->affiliate_url}'>COMPRAR AGORA</a>";
                } elseif (!empty($promotion->url)) {
                    $text .= "\n\n🔗 <a href='{$promotion->url}'>VER PROMOÇÃO</a>";
                }
            }

            // Adicionar canal de origem
            $text .= "\n\n📢 @PegouPromo";

            // Se tem imagem, enviar como foto com caption
            if (!empty($promotion->image)) {
                $response = $this->sendPhoto($promotion->image, $text);
            } else {
                $response = $this->sendMessage($text);
            }

            if ($response && $response['ok']) {
                Log::info('Promoção enviada com sucesso para Telegram', [
                    'promotion_id' => $promotion->id,
                    'message_id' => $response['result']['message_id'] ?? null
                ]);
                return true;
            } else {
                Log::error('Falha no envio para Telegram', [
                    'promotion_id' => $promotion->id,
                    'response' => $response
                ]);
                return false;
            }

        } catch (\Exception $e) {
            Log::error('Erro ao enviar promoção para Telegram: ' . $e->getMessage(), [
                'promotion_id' => $promotion->id
            ]);
            return false;
        }
    }

    protected function formatAIGeneratedPost(Promotion $promotion)
    {
        $text = $promotion->gemini_generated_post;

        // Adicionar informações de preço se não estiverem no post
        if ($promotion->discounted_price && !str_contains($text, 'R$')) {
            $priceInfo = "\n\n💰 ";
            if ($promotion->original_price) {
                $priceInfo .= "<s>R$ " . number_format((float)$promotion->original_price, 2, ',', '.') . "</s> ";
            }
            $priceInfo .= "R$ " . number_format((float)$promotion->discounted_price, 2, ',', '.');

            if ($promotion->discount_percentage) {
                $priceInfo .= " ({$promotion->discount_percentage}% OFF)";
            }

            $text .= $priceInfo;
        }

        // Adicionar loja se não estiver no post
        if ($promotion->store && !str_contains(strtolower($text), strtolower($promotion->store))) {
            $text .= "\n🏪 " . $promotion->store;
        }

        return $text;
    }

    protected function formatManualPost(Promotion $promotion)
    {
        $text = "🔥 <b>{$promotion->title}</b>\n\n";

        if ($promotion->description) {
            $text .= "{$promotion->description}\n\n";
        }

        // Preços
        if ($promotion->discounted_price) {
            $text .= "💰 ";
            if ($promotion->original_price) {
                $text .= "<s>R$ " . number_format((float)$promotion->original_price, 2, ',', '.') . "</s> ";
            }
            $text .= "R$ " . number_format((float)$promotion->discounted_price, 2, ',', '.');

            if ($promotion->discount_percentage) {
                $text .= " <b>({$promotion->discount_percentage}% OFF)</b>";
            }
            $text .= "\n\n";
        }

        // Loja
        if ($promotion->store) {
            $text .= "🏪 {$promotion->store}\n";
        }

        // Call to action
        $text .= "\n⚡ APROVEITE ENQUANTO ESTÁ DISPONÍVEL!";

        return $text;
    }

    protected function sendMessage(string $text)
    {
        $response = Http::timeout(30)->post("https://api.telegram.org/bot{$this->token}/sendMessage", [
            'chat_id' => $this->channelId,
            'text' => $text,
            'parse_mode' => 'HTML',
            'disable_web_page_preview' => false,
            'disable_notification' => false
        ]);

        return $response->successful() ? $response->json() : null;
    }

    protected function sendPhoto(string $photoUrl, string $caption)
    {
        $response = Http::timeout(30)->post("https://api.telegram.org/bot{$this->token}/sendPhoto", [
            'chat_id' => $this->channelId,
            'photo' => $photoUrl,
            'caption' => $caption,
            'parse_mode' => 'HTML',
            'disable_notification' => false
        ]);

        return $response->successful() ? $response->json() : null;
    }

    public function sendTestMessage(string $message = null)
    {
        try {
            $text = $message ?? "🤖 Teste de conexão - Bot funcionando perfeitamente!\n\n📅 " . now()->format('d/m/Y H:i:s');

            $response = $this->sendMessage($text);

            return $response && $response['ok'];

        } catch (\Exception $e) {
            Log::error('Erro no teste do Telegram: ' . $e->getMessage());
            return false;
        }
    }

    public function validateConfiguration()
    {
        $errors = [];

        if (empty($this->token)) {
            $errors[] = 'Token do bot Telegram não configurado';
        }

        if (empty($this->channelId)) {
            $errors[] = 'ID do canal Telegram não configurado';
        }

        // Testar conexão se as credenciais estão configuradas
        if (empty($errors)) {
            try {
                $response = Http::timeout(10)->get("https://api.telegram.org/bot{$this->token}/getMe");
                if (!$response->successful()) {
                    $errors[] = 'Token do bot inválido ou sem acesso à API';
                }
            } catch (\Exception $e) {
                $errors[] = 'Erro de conexão com a API do Telegram: ' . $e->getMessage();
            }
        }

        return empty($errors) ? true : $errors;
    }

    public function getChannelInfo()
    {
        try {
            $response = Http::timeout(10)->get("https://api.telegram.org/bot{$this->token}/getChat", [
                'chat_id' => $this->channelId
            ]);

            if ($response->successful()) {
                return $response->json()['result'] ?? null;
            }

            return null;

        } catch (\Exception $e) {
            Log::error('Erro ao obter informações do canal: ' . $e->getMessage());
            return null;
        }
    }

    public function getBotInfo()
    {
        try {
            $response = Http::timeout(10)->get("https://api.telegram.org/bot{$this->token}/getMe");

            if ($response->successful()) {
                return $response->json()['result'] ?? null;
            }

            return null;

        } catch (\Exception $e) {
            Log::error('Erro ao obter informações do bot: ' . $e->getMessage());
            return null;
        }
    }
}
