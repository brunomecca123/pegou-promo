<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiService
{
    protected $apiKey;
    protected $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash-exp:generateContent';

    public function __construct()
    {
        $this->apiKey = config('services.gemini.api_key', env('GEMINI_API_KEY'));
    }

    public function generatePromotionPost(array $promotionData)
    {
        try {
            $prompt = $this->buildPromotionPrompt($promotionData);
            
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'x-goog-api-key' => $this->apiKey
            ])->timeout(30)->post($this->baseUrl, [
                'contents' => [
                    [
                        'role' => 'user',
                        'parts' => [
                            [
                                'text' => $prompt
                            ]
                        ]
                    ]
                ],
                'generationConfig' => [
                    'temperature' => 0.7,
                    'topP' => 0.9,
                    'maxOutputTokens' => 1000
                ]
            ]);

            if (!$response->successful()) {
                Log::error('Erro na API do Gemini: ' . $response->body());
                return null;
            }

            $data = $response->json();
            
            if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
                return trim($data['candidates'][0]['content']['parts'][0]['text']);
            }

            return null;

        } catch (\Exception $e) {
            Log::error('Erro ao gerar post com Gemini: ' . $e->getMessage());
            return null;
        }
    }

    protected function buildPromotionPrompt(array $promotion)
    {
        $prompt = "Você é um especialista em marketing de afiliados e criação de conteúdo para redes sociais, especificamente para canais de promoções no Telegram e WhatsApp.\n\n";
        
        $prompt .= "DADOS DA PROMOÇÃO:\n";
        $prompt .= "• Produto: {$promotion['title']}\n";
        $prompt .= "• Loja: {$promotion['store']}\n";
        
        if (isset($promotion['original_price']) && $promotion['original_price']) {
            $prompt .= "• Preço Original: R$ " . number_format($promotion['original_price'], 2, ',', '.') . "\n";
        }
        
        if (isset($promotion['discounted_price']) && $promotion['discounted_price']) {
            $prompt .= "• Preço com Desconto: R$ " . number_format($promotion['discounted_price'], 2, ',', '.') . "\n";
        }
        
        if (isset($promotion['discount_percentage']) && $promotion['discount_percentage']) {
            $prompt .= "• Desconto: {$promotion['discount_percentage']}%\n";
        }
        
        if (isset($promotion['description']) && $promotion['description']) {
            $prompt .= "• Descrição: {$promotion['description']}\n";
        }
        
        if (isset($promotion['category']) && $promotion['category']) {
            $prompt .= "• Categoria: {$promotion['category']}\n";
        }

        $prompt .= "\nCRIE UM POST ATRATIVO SEGUINDO ESTAS DIRETRIZES:\n\n";
        
        $prompt .= "🎯 ESTRUTURA OBRIGATÓRIA:\n";
        $prompt .= "1. TÍTULO CHAMATIVO com emoji relevante\n";
        $prompt .= "2. DESTAQUE DO DESCONTO (se houver)\n";
        $prompt .= "3. CARACTERÍSTICAS PRINCIPAIS do produto (máximo 3 pontos)\n";
        $prompt .= "4. CALL TO ACTION motivador\n";
        $prompt .= "5. HASHTAGS relevantes (máximo 3)\n\n";
        
        $prompt .= "📝 REGRAS DE ESCRITA:\n";
        $prompt .= "• Use linguagem informal e entusiasmada\n";
        $prompt .= "• Máximo 200 caracteres\n";
        $prompt .= "• Use emojis estrategicamente (não exagere)\n";
        $prompt .= "• Crie urgência sem ser invasivo\n";
        $prompt .= "• Foque nos benefícios para o consumidor\n";
        $prompt .= "• Use termos como 'IMPERDÍVEL', 'OFERTA LIMITADA', 'PREÇO HISTÓRICO'\n\n";
        
        $prompt .= "🚫 NÃO INCLUA:\n";
        $prompt .= "• Links (serão adicionados automaticamente)\n";
        $prompt .= "• Termos duvidosos ou spam\n";
        $prompt .= "• Informações não confirmadas\n";
        $prompt .= "• Comparações com concorrentes\n\n";
        
        $prompt .= "💡 EXEMPLOS DE BONS POSTS:\n";
        $prompt .= "\"🔥 PREÇO HISTÓRICO! iPhone 15 com 67% OFF\n";
        $prompt .= "✅ Câmera 48MP\n";
        $prompt .= "✅ Chip A17 Pro\n";
        $prompt .= "✅ Entrega grátis\n";
        $prompt .= "⚡ CORRE QUE ACABA! #iPhone #Oferta #TechDeals\"\n\n";
        
        $prompt .= "\"🎮 GAMER, BORA! Headset HyperX 45% OFF\n";
        $prompt .= "✅ Som surround 7.1\n";
        $prompt .= "✅ Microfone removível\n";
        $prompt .= "✅ Compatível PC/Console\n";
        $prompt .= "🎯 Última chance! #Gaming #Headset #Promo\"\n\n";
        
        $prompt .= "Agora crie um post seguindo exatamente esse padrão para a promoção informada:";

        return $prompt;
    }

    public function generateVariations(string $originalPost, int $count = 3)
    {
        try {
            $prompt = "Você recebeu este post de promoção:\n\n\"$originalPost\"\n\n";
            $prompt .= "Crie $count variações diferentes deste post mantendo:\n";
            $prompt .= "• A mesma informação essencial\n";
            $prompt .= "• O tom entusiasmado\n";
            $prompt .= "• A estrutura com emojis\n";
            $prompt .= "• Máximo 200 caracteres cada\n\n";
            $prompt .= "Mas mudando:\n";
            $prompt .= "• As palavras de call-to-action\n";
            $prompt .= "• Os emojis usados\n";
            $prompt .= "• A ordem das informações\n\n";
            $prompt .= "Retorne apenas as variações, uma por linha, numeradas.";

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'x-goog-api-key' => $this->apiKey
            ])->timeout(30)->post($this->baseUrl, [
                'contents' => [
                    [
                        'role' => 'user',
                        'parts' => [
                            [
                                'text' => $prompt
                            ]
                        ]
                    ]
                ],
                'generationConfig' => [
                    'temperature' => 0.8,
                    'topP' => 0.95,
                    'maxOutputTokens' => 800
                ]
            ]);

            if (!$response->successful()) {
                Log::error('Erro na API do Gemini para variações: ' . $response->body());
                return [];
            }

            $data = $response->json();
            
            if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
                $text = trim($data['candidates'][0]['content']['parts'][0]['text']);
                // Dividir as variações por linha e limpar
                $variations = array_map('trim', explode("\n", $text));
                return array_filter($variations, function($v) { 
                    return !empty($v) && strlen($v) > 20; 
                });
            }

            return [];

        } catch (\Exception $e) {
            Log::error('Erro ao gerar variações com Gemini: ' . $e->getMessage());
            return [];
        }
    }

    public function optimizePost(string $post, string $platform = 'telegram')
    {
        try {
            $platformRules = [
                'telegram' => 'Telegram (permite formatação HTML básica, até 4096 caracteres)',
                'whatsapp' => 'WhatsApp (sem formatação especial, até 65536 caracteres)',
                'instagram' => 'Instagram (com hashtags obrigatórias, até 2200 caracteres)'
            ];

            $prompt = "Otimize este post de promoção para {$platformRules[$platform]}:\n\n";
            $prompt .= "\"$post\"\n\n";
            $prompt .= "Mantenha o conteúdo essencial mas adapte para as melhores práticas da plataforma.";

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'x-goog-api-key' => $this->apiKey
            ])->timeout(30)->post($this->baseUrl, [
                'contents' => [
                    [
                        'role' => 'user',
                        'parts' => [
                            [
                                'text' => $prompt
                            ]
                        ]
                    ]
                ]
            ]);

            if (!$response->successful()) {
                return $post; // Retorna o original se falhar
            }

            $data = $response->json();
            
            if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
                return trim($data['candidates'][0]['content']['parts'][0]['text']);
            }

            return $post;

        } catch (\Exception $e) {
            Log::error('Erro ao otimizar post com Gemini: ' . $e->getMessage());
            return $post;
        }
    }
}
