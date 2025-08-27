<?php

namespace App\Services;

use App\Models\Promotion;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PromobitScrapperService
{
    protected $baseUrl = 'https://www.promobit.com.br';
    protected $userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36';

    public function scrapePromotions($store = null, $limit = 10)
    {
        try {
            $url = $this->baseUrl . '/promocoes';
            if ($store) {
                $url .= '/loja/' . $store;
            }

            Log::info('Iniciando scraping do Promobit', ['url' => $url, 'limit' => $limit]);

            $response = Http::withHeaders([
                'User-Agent' => $this->userAgent,
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                'Accept-Language' => 'pt-BR,pt;q=0.9,en;q=0.8',
                'Connection' => 'keep-alive',
                'Upgrade-Insecure-Requests' => '1',
            ])->withOptions([
                'curl' => [
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_MAXREDIRS => 3,
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_SSL_VERIFYHOST => false,
                ]
            ])->timeout(30)->get($url);

            if (!$response->successful()) {
                Log::error('Erro ao acessar Promobit: ' . $response->status());

                // Fallback para dados simulados em caso de erro
                return $this->getSimulatedPromotions($store, $limit);
            }

            $html = $response->body();
            $promotions = $this->parsePromotions($html, $limit);

            // Se não encontrou nenhuma promoção, usar dados simulados
            if (empty($promotions)) {
                Log::warning('Nenhuma promoção encontrada no scraping, usando dados simulados');
                return $this->getSimulatedPromotions($store, $limit);
            }

            return $promotions;

        } catch (\Exception $e) {
            Log::error('Erro no scrapping do Promobit: ' . $e->getMessage());

            // Fallback para dados simulados
            return $this->getSimulatedPromotions($store, $limit);
        }
    }

    protected function getSimulatedPromotions($store = null, $limit = 10)
    {
        Log::info('Gerando promoções simuladas para teste', ['store' => $store, 'limit' => $limit]);

        $simulatedPromotions = [
            [
                'title' => 'Smartphone Samsung Galaxy A54 128GB - Promoção Imperdível',
                'description' => 'Smartphone com tela Super AMOLED de 6.4", câmera tripla de 50MP e bateria de 5000mAh',
                'url' => '',
                'source_url' => 'https://www.promobit.com.br/promocao/smartphone-samsung-galaxy-a54-teste',
                'image' => 'https://images.samsung.com/is/image/samsung/p6pim/br/sm-a546elguzto/gallery/br-galaxy-a54-5g-sm-a546-sm-a546elguzto-535062221',
                'original_price' => 1899.90,
                'discounted_price' => 1299.90,
                'discount_percentage' => 32,
                'store' => $store ?: 'Amazon',
                'category' => 'eletrônicos',
                'status' => 'draft'
            ],
            [
                'title' => 'Fone de Ouvido Bluetooth JBL Tune 760NC - Com Cancelamento de Ruído',
                'description' => 'Fone over-ear com cancelamento ativo de ruído e até 35h de bateria',
                'url' => '',
                'source_url' => 'https://www.promobit.com.br/promocao/fone-jbl-tune-760nc-teste',
                'image' => 'https://www.jbl.com.br/dw/image/v2/AAUJ_PRD/on/demandware.static/-/Sites-masterCatalog_Harman/default/dwa4c5c5c5/JBL_TUNE760NC_ProductImage_Black_Front.png',
                'original_price' => 599.90,
                'discounted_price' => 359.90,
                'discount_percentage' => 40,
                'store' => $store ?: 'Amazon',
                'category' => 'eletrônicos',
                'status' => 'draft'
            ],
            [
                'title' => 'Echo Dot 5ª Geração Alexa - Smart Speaker',
                'description' => 'Alto-falante inteligente com Alexa, som mais potente e hub de casa inteligente',
                'url' => '',
                'source_url' => 'https://www.promobit.com.br/promocao/echo-dot-5-geracao-teste',
                'image' => 'https://m.media-amazon.com/images/I/71EJgo6+wuL._AC_SL1000_.jpg',
                'original_price' => 349.90,
                'discounted_price' => 199.90,
                'discount_percentage' => 43,
                'store' => $store ?: 'Amazon',
                'category' => 'eletrônicos',
                'status' => 'draft'
            ],
            [
                'title' => 'Fire TV Stick 4K Max com Controle por Voz Alexa',
                'description' => 'Streaming device 4K com Wi-Fi 6 e controle remoto por voz',
                'url' => '',
                'source_url' => 'https://www.promobit.com.br/promocao/fire-tv-stick-4k-max-teste',
                'image' => 'https://m.media-amazon.com/images/I/51TjJOTfslL._AC_SL1000_.jpg',
                'original_price' => 449.90,
                'discounted_price' => 279.90,
                'discount_percentage' => 38,
                'store' => $store ?: 'Amazon',
                'category' => 'eletrônicos',
                'status' => 'draft'
            ],
            [
                'title' => 'Kindle 11ª Geração - Agora com iluminação frontal',
                'description' => 'E-reader com tela de 6.8", iluminação ajustável e bateria que dura semanas',
                'url' => '',
                'source_url' => 'https://www.promobit.com.br/promocao/kindle-11-geracao-teste',
                'image' => 'https://m.media-amazon.com/images/I/61YGlLB8xDL._AC_SL1000_.jpg',
                'original_price' => 449.90,
                'discounted_price' => 329.90,
                'discount_percentage' => 27,
                'store' => $store ?: 'Amazon',
                'category' => 'livros',
                'status' => 'draft'
            ]
        ];

        return array_slice($simulatedPromotions, 0, $limit);
    }

    protected function extractJsonData($html)
    {
        // Tentar encontrar dados JSON embutidos no HTML
        $patterns = [
            '/<script[^>]*id=["\']__NEXT_DATA__["\'][^>]*>(.*?)<\/script>/s', // Next.js - prioridade
            '/<script[^>]*type=["\']application\/json["\'][^>]*>(.*?)<\/script>/s',
            '/window\.__INITIAL_STATE__\s*=\s*({.*?});/s',
            '/window\.__PRELOADED_STATE__\s*=\s*({.*?});/s',
            '/window\.initData\s*=\s*({.*?});/s'
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $html, $matches)) {
                $jsonString = trim($matches[1]);
                $data = json_decode($jsonString, true);
                if (json_last_error() === JSON_ERROR_NONE && !empty($data)) {
                    Log::info('JSON data encontrado e decodificado com sucesso', [
                        'pattern' => $pattern,
                        'data_size' => strlen($jsonString),
                        'top_keys' => array_keys($data)
                    ]);
                    return $data;
                }
            }
        }

        Log::warning('Nenhum dado JSON válido encontrado no HTML');
        return null;
    }

    protected function cleanText($text)
    {
        return trim(preg_replace('/\s+/', ' ', strip_tags($text)));
    }

    protected function normalizeUrl($url)
    {
        if (empty($url)) return '';

        if (str_starts_with($url, '/')) {
            return $this->baseUrl . $url;
        }

        return $url;
    }

    protected function extractDescription($node, $xpath)
    {
        // Buscar por descrições em vários seletores
        $descSelectors = [
            ".//div[contains(@class, 'description')]",
            ".//p",
            ".//div[contains(@class, 'content')]",
            ".//span[contains(@class, 'text')]"
        ];

        foreach ($descSelectors as $selector) {
            $descNodes = $xpath->query($selector, $node);
            if ($descNodes->length > 0) {
                $desc = $this->cleanText($descNodes->item(0)->textContent);
                if (strlen($desc) > 20) {
                    return $desc;
                }
            }
        }

        return '';
    }

    protected function extractImage($node, $xpath)
    {
        $imgNodes = $xpath->query(".//img", $node);
        if ($imgNodes->length > 0) {
            $src = $imgNodes->item(0)->getAttribute('src');
            if (empty($src)) {
                $src = $imgNodes->item(0)->getAttribute('data-src');
            }
            return $this->normalizeUrl($src);
        }
        return '';
    }

    protected function extractPrice($node, $xpath)
    {
        $priceSelectors = [
            ".//span[contains(@class, 'price')]",
            ".//div[contains(@class, 'price')]",
            ".//span[contains(text(), 'R$')]",
            ".//div[contains(text(), 'R$')]"
        ];

        foreach ($priceSelectors as $selector) {
            $priceNodes = $xpath->query($selector, $node);
            if ($priceNodes->length > 0) {
                $priceText = $this->cleanText($priceNodes->item(0)->textContent);
                $price = $this->parsePrice($priceText);
                if ($price) {
                    return $price;
                }
            }
        }

        return null;
    }

    protected function extractStore($node, $xpath)
    {
        $storeSelectors = [
            ".//span[contains(@class, 'store')]",
            ".//div[contains(@class, 'store')]",
            ".//img[contains(@alt, 'Amazon')]/..",
            ".//span[contains(text(), 'Amazon')]"
        ];

        foreach ($storeSelectors as $selector) {
            $storeNodes = $xpath->query($selector, $node);
            if ($storeNodes->length > 0) {
                $store = $this->cleanText($storeNodes->item(0)->textContent);
                if (!empty($store)) {
                    return $store;
                }
            }
        }

        return null;
    }

    protected function parsePrice($priceText)
    {
        // Remover espaços e converter para minúsculas
        $clean = strtolower(trim($priceText));

        // Buscar padrões de preço em reais
        if (preg_match('/r\$\s*(\d+(?:[.,]\d+)*)/', $clean, $matches)) {
            $priceStr = str_replace(',', '.', $matches[1]);
            return (float) $priceStr;
        }

        // Buscar apenas números com vírgula ou ponto
        if (preg_match('/(\d+(?:[.,]\d{2}))/', $clean, $matches)) {
            $priceStr = str_replace(',', '.', $matches[1]);
            return (float) $priceStr;
        }

        return null;
    }

    protected function extractOriginalPrice($node, $xpath)
    {
        $originalPriceSelectors = [
            ".//span[contains(@class, 'original-price')]",
            ".//span[contains(@class, 'old-price')]",
            ".//del//span[contains(text(), 'R$')]",
            ".//strike//span[contains(text(), 'R$')]",
            ".//s//span[contains(text(), 'R$')]",
            ".//span[contains(@style, 'text-decoration: line-through')]"
        ];

        foreach ($originalPriceSelectors as $selector) {
            $priceNodes = $xpath->query($selector, $node);
            if ($priceNodes->length > 0) {
                $priceText = $this->cleanText($priceNodes->item(0)->textContent);
                $price = $this->parsePrice($priceText);
                if ($price) {
                    return $price;
                }
            }
        }

        return null;
    }

    protected function extractPromotionsFromJson($data)
    {
        $promotions = [];

        try {
            // Buscar em estruturas específicas do Next.js do Promobit
            $paths = [
                'props.pageProps.threads', // Estrutura principal do Promobit
                'props.pageProps.deals',
                'props.pageProps.promotions',
                'props.pageProps.offers',
                'props.pageProps.initialOffers',
                'pageProps.threads',
                'pageProps.deals',
                'pageProps.offers',
                'threads',
                'deals',
                'offers',
                'promotions'
            ];

            foreach ($paths as $path) {
                $items = $this->getJsonValueByPath($data, $path);
                if (is_array($items) && !empty($items)) {
                    Log::info("Encontrados dados em: {$path}", ['count' => count($items)]);
                    foreach ($items as $item) {
                        $promotion = $this->parsePromotionFromJson($item);
                        if ($promotion) {
                            $promotions[] = $promotion;
                        }
                    }

                    if (!empty($promotions)) {
                        break;
                    }
                }
            }

            // Se não encontrou promoções nos paths principais,
            // tentar buscar por estruturas secundárias
            if (empty($promotions)) {
                Log::info('Tentando extrair de estruturas secundárias');
                $promotions = $this->extractFromSecondaryStructures($data);
            }

        } catch (\Exception $e) {
            Log::error('Erro ao extrair promoções do JSON: ' . $e->getMessage());
        }

        return $promotions;
    }

    protected function extractFromSecondaryStructures($data)
    {
        $promotions = [];

        try {
            // Buscar em pageProps por qualquer array que possa conter ofertas
            if (isset($data['props']['pageProps'])) {
                $pageProps = $data['props']['pageProps'];

                foreach ($pageProps as $key => $value) {
                    if (is_array($value) && !empty($value)) {
                        Log::info("Analisando estrutura: {$key}", ['count' => count($value)]);

                        // Verificar se parece com uma lista de ofertas
                        $firstItem = reset($value);
                        if (is_array($firstItem) && $this->looksLikePromotion($firstItem)) {
                            Log::info("Estrutura {$key} parece conter promoções");

                            foreach ($value as $item) {
                                $promotion = $this->parsePromotionFromJson($item);
                                if ($promotion) {
                                    $promotions[] = $promotion;
                                }
                            }

                            if (!empty($promotions)) {
                                break;
                            }
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('Erro ao extrair de estruturas secundárias: ' . $e->getMessage());
        }

        return $promotions;
    }

    protected function looksLikePromotion($item)
    {
        if (!is_array($item)) {
            return false;
        }

        // Verificar se tem campos típicos de uma promoção
        $promotionFields = ['title', 'price', 'discount', 'url', 'link', 'store', 'thread_title', 'thread_url'];
        $fieldCount = 0;

        foreach ($promotionFields as $field) {
            if (isset($item[$field])) {
                $fieldCount++;
            }
        }

        return $fieldCount >= 2; // Se tem pelo menos 2 campos típicos
    }

    protected function getJsonValueByPath($data, $path)
    {
        $keys = explode('.', $path);
        $current = $data;

        foreach ($keys as $key) {
            if (is_array($current) && isset($current[$key])) {
                $current = $current[$key];
            } else {
                return null;
            }
        }

        return $current;
    }

    protected function parsePromotionFromJson($item)
    {
        try {
            // Estrutura típica dos dados JSON do Promobit
            $title = $item['title'] ?? $item['thread_title'] ?? '';
            $description = $item['description'] ?? $item['excerpt'] ?? '';
            $url = $item['url'] ?? $item['thread_url'] ?? '';
            $image = $item['image'] ?? $item['thread_image'] ?? '';
            $price = $item['price'] ?? $item['promo_price'] ?? null;
            $originalPrice = $item['original_price'] ?? null;
            $store = $item['store'] ?? $item['retailer'] ?? '';
            $discount = $item['discount_percentage'] ?? null;

            if (empty($title)) {
                return null;
            }

            return [
                'title' => $title,
                'description' => $description,
                'url' => '',
                'source_url' => $this->normalizeUrl($url),
                'image' => $this->normalizeUrl($image),
                'original_price' => $originalPrice,
                'discounted_price' => $price,
                'discount_percentage' => $discount,
                'store' => $store,
                'category' => $this->detectCategory($title, $description),
                'status' => 'draft'
            ];

        } catch (\Exception $e) {
            Log::error('Erro ao parsear promoção do JSON: ' . $e->getMessage());
            return null;
        }
    }

    protected function extractPromotionFromNode($node, $xpath)
    {
        try {
            $title = $this->extractTitle($node, $xpath);
            if (empty($title)) {
                return null;
            }

            // Filtrar cards genéricos que não são promoções específicas
            $genericTitles = [
                'Melhores Ofertas na Amazon',
                'Ofertas da Amazon',
                'Descontos Que Você Não Pode Perder',
                'Promoções Amazon'
            ];

            foreach ($genericTitles as $genericTitle) {
                if (stripos($title, $genericTitle) !== false) {
                    Log::info("Filtrando card genérico: {$title}");
                    return null;
                }
            }

            $description = $this->extractDescription($node, $xpath);
            $image = $this->extractImage($node, $xpath);
            $price = $this->extractPrice($node, $xpath);
            $store = $this->extractStore($node, $xpath);

            // Buscar link da promoção - melhorado para pegar href do próprio nó se for um link
            $link = '';
            if ($node->nodeName === 'a' && $node->hasAttribute('href')) {
                $link = $this->normalizeUrl($node->getAttribute('href'));
            } else {
                $linkNodes = $xpath->query(".//a[@href]", $node);
                if ($linkNodes->length > 0) {
                    $link = $this->normalizeUrl($linkNodes->item(0)->getAttribute('href'));
                }
            }

            // Extrair preço original se houver
            $originalPrice = $this->extractOriginalPrice($node, $xpath);

            // Calcular desconto se temos ambos os preços
            $discountPercentage = null;
            if ($price && $originalPrice && $originalPrice > $price) {
                $discountPercentage = round((($originalPrice - $price) / $originalPrice) * 100);
            }

            return [
                'title' => $title,
                'description' => $description,
                'url' => '',
                'source_url' => $link,
                'image' => $image,
                'original_price' => $originalPrice,
                'discounted_price' => $price,
                'discount_percentage' => $discountPercentage,
                'store' => $store ?: 'Amazon',
                'category' => $this->detectCategory($title, $description),
                'status' => 'draft'
            ];

        } catch (\Exception $e) {
            Log::error('Erro ao extrair promoção do nó: ' . $e->getMessage());
            return null;
        }
    }

    protected function extractTitle($node, $xpath)
    {
        $titleSelectors = [
            ".//h1",
            ".//h2",
            ".//h3",
            ".//span[contains(@class, 'title')]",
            ".//div[contains(@class, 'title')]",
            ".//a[contains(@class, 'title')]"
        ];

        foreach ($titleSelectors as $selector) {
            $titleNodes = $xpath->query($selector, $node);
            if ($titleNodes->length > 0) {
                $title = $this->cleanText($titleNodes->item(0)->textContent);
                if (strlen($title) > 10) {
                    return $title;
                }
            }
        }

        return '';
    }

    protected function parsePromotions($html, $limit)
    {
        $promotions = [];

        Log::info('Iniciando parsePromotions - HTML size: ' . strlen($html) . ' bytes');

        // Primeiro tentar extrair dados JSON
        $jsonData = $this->extractJsonData($html);
        if ($jsonData) {
            Log::info('Dados JSON encontrados, tentando extrair promoções');
            $promotions = $this->extractPromotionsFromJson($jsonData);
            if (!empty($promotions)) {
                Log::info('Promoções extraídas do JSON: ' . count($promotions));
                return array_slice($promotions, 0, $limit);
            }
        }

        // Se JSON não funcionou, tentar parsing HTML tradicional
        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML($html);
        libxml_clear_errors();

        $xpath = new \DOMXPath($dom);

        // Múltiplos seletores para diferentes layouts
        $selectors = [
            "//div[contains(@class, 'item')]", // Prioridade - encontrado 348 elementos
            "//div[contains(@class, 'thread-card')]",
            "//div[contains(@class, 'post-thread')]",
            "//article[contains(@class, 'thread')]",
            "//div[contains(@class, 'deal-card')]",
            "//div[contains(@class, 'promotion')]",
            "//div[@data-testid='thread-card']",
            "//div[contains(@class, 'promotion-card')]",
            "//div[contains(@class, 'offer-card')]",
            "//a[contains(@href, '/promocao/')]",
            "//a[contains(@href, '/oferta/')]"
        ];

        foreach ($selectors as $selector) {
            $nodes = $xpath->query($selector);
            Log::info("Seletor '{$selector}' encontrou {$nodes->length} elementos");

            if ($nodes->length > 0) {
                $count = 0;
                foreach ($nodes as $node) {
                    if ($count >= $limit) break;

                    $promotion = $this->extractPromotionFromNode($node, $xpath);
                    if ($promotion) {
                        $promotions[] = $promotion;
                        $count++;
                    }
                }

                if (!empty($promotions)) {
                    Log::info('Promoções extraídas do HTML: ' . count($promotions));
                    return $promotions;
                }
            }
        }

        // Se nenhum método funcionou, usar dados simulados como fallback
        Log::warning('Nenhuma promoção encontrada no HTML, usando dados simulados');
        return $this->getSimulatedPromotions($limit);
    }

    protected function extractPromotionData($card, $xpath)
    {
        try {
            // Múltiplos seletores para o título
            $titleSelectors = [
                ".//h2[contains(@class, 'thread-title')]//a",
                ".//h3[contains(@class, 'thread-title')]//a",
                ".//a[contains(@class, 'thread-title')]",
                ".//h2//a",
                ".//h3//a",
                ".//a[contains(@href, '/promocao/')]"
            ];

            $title = '';
            $promoUrl = '';

            foreach ($titleSelectors as $selector) {
                $nodes = $xpath->query($selector, $card);
                if ($nodes->length > 0) {
                    $title = trim($nodes->item(0)->textContent);
                    $promoUrl = $nodes->item(0)->getAttribute('href');
                    break;
                }
            }

            // Se não encontrou título, tentar outros métodos
            if (empty($title)) {
                $allLinks = $xpath->query(".//a", $card);
                foreach ($allLinks as $link) {
                    $linkText = trim($link->textContent);
                    $href = $link->getAttribute('href');
                    if (!empty($linkText) && strlen($linkText) > 10 && str_contains($href, '/promocao/')) {
                        $title = $linkText;
                        $promoUrl = $href;
                        break;
                    }
                }
            }

            // Normalizar URL
            if ($promoUrl && !str_starts_with($promoUrl, 'http')) {
                $promoUrl = $this->baseUrl . $promoUrl;
            }

            // Múltiplos seletores para preço
            $priceSelectors = [
                ".//span[contains(@class, 'thread-price')]",
                ".//span[contains(@class, 'price')]",
                ".//div[contains(@class, 'price')]",
                ".//strong[contains(text(), 'R$')]",
                ".//span[contains(text(), 'R$')]"
            ];

            $priceText = '';
            foreach ($priceSelectors as $selector) {
                $nodes = $xpath->query($selector, $card);
                if ($nodes->length > 0) {
                    $priceText = trim($nodes->item(0)->textContent);
                    break;
                }
            }

            // Múltiplos seletores para loja
            $storeSelectors = [
                ".//span[contains(@class, 'thread-store')]",
                ".//span[contains(@class, 'store')]",
                ".//div[contains(@class, 'store')]",
                ".//span[contains(@class, 'retailer')]"
            ];

            $store = '';
            foreach ($storeSelectors as $selector) {
                $nodes = $xpath->query($selector, $card);
                if ($nodes->length > 0) {
                    $store = trim($nodes->item(0)->textContent);
                    break;
                }
            }

            // Extrair imagem
            $imageNodes = $xpath->query(".//img", $card);
            $image = '';
            if ($imageNodes->length > 0) {
                $img = $imageNodes->item(0);
                $image = $img->getAttribute('src') ?: $img->getAttribute('data-src') ?: $img->getAttribute('data-lazy');
            }

            // Extrair descrição
            $descriptionSelectors = [
                ".//div[contains(@class, 'thread-description')]",
                ".//div[contains(@class, 'description')]",
                ".//p[contains(@class, 'description')]"
            ];

            $description = '';
            foreach ($descriptionSelectors as $selector) {
                $nodes = $xpath->query($selector, $card);
                if ($nodes->length > 0) {
                    $description = trim($nodes->item(0)->textContent);
                    break;
                }
            }

            Log::debug('Dados extraídos:', [
                'title' => $title,
                'promoUrl' => $promoUrl,
                'priceText' => $priceText,
                'store' => $store,
                'image' => $image
            ]);

            if (empty($title)) {
                Log::warning('Título vazio, descartando card');
                return null;
            }

            // Parse dos preços
            $prices = $this->parsePrices($priceText);            return [
                'title' => $title,
                'description' => $description,
                'url' => '',  // Será preenchido com o link de afiliado
                'source_url' => $promoUrl,
                'image' => $image,
                'original_price' => $prices['original'],
                'discounted_price' => $prices['discounted'],
                'discount_percentage' => $prices['percentage'],
                'store' => $store,
                'category' => $this->detectCategory($title, $description),
                'status' => 'draft'
            ];

        } catch (\Exception $e) {
            Log::error('Erro ao extrair dados da promoção: ' . $e->getMessage());
            return null;
        }
    }

    protected function parsePrices($priceText)
    {
        $original = null;
        $discounted = null;
        $percentage = null;

        // Remover caracteres especiais e normalizar
        $cleanText = preg_replace('/[^\d,.\s\-R$%]/', '', $priceText);

        // Buscar por padrões de preço
        if (preg_match('/R\$\s*(\d+(?:,\d{2})?)/i', $cleanText, $matches)) {
            $discounted = (float) str_replace(',', '.', $matches[1]);
        }

        // Buscar por desconto em %
        if (preg_match('/(\d+)%/', $cleanText, $matches)) {
            $percentage = (int) $matches[1];
            if ($discounted && $percentage) {
                $original = $discounted / (1 - $percentage / 100);
            }
        }

        return [
            'original' => $original,
            'discounted' => $discounted,
            'percentage' => $percentage
        ];
    }

    protected function detectCategory($title, $description)
    {
        $text = strtolower($title . ' ' . $description);

        $categories = [
            'eletrônicos' => ['smartphone', 'celular', 'notebook', 'tablet', 'fone', 'headphone', 'tv', 'monitor'],
            'casa' => ['cama', 'mesa', 'banho', 'cozinha', 'decoração', 'móveis'],
            'moda' => ['roupa', 'calça', 'camisa', 'tênis', 'sapato', 'bolsa', 'relógio'],
            'saúde' => ['vitamina', 'suplemento', 'medicamento', 'perfume'],
            'livros' => ['livro', 'kindle', 'ebook']
        ];

        foreach ($categories as $category => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($text, $keyword)) {
                    return $category;
                }
            }
        }

        return 'geral';
    }

    protected function isValidPromotion($promotion)
    {
        // Validações básicas
        if (empty($promotion['title']) || empty($promotion['source_url'])) {
            return false;
        }

        // Filtrar produtos muito baratos ou muito caros (possível erro)
        if ($promotion['discounted_price'] &&
            ($promotion['discounted_price'] < 1 || $promotion['discounted_price'] > 10000)) {
            return false;
        }

        // Verificar se já existe no banco
        $exists = Promotion::where('source_url', $promotion['source_url'])->exists();

        return !$exists;
    }

    public function getPromotionDetails($promoUrl)
    {
        try {
            $response = Http::withHeaders([
                'User-Agent' => $this->userAgent
            ])->timeout(30)->get($promoUrl);

            if (!$response->successful()) {
                return null;
            }

            $html = $response->body();
            $dom = new \DOMDocument();
            libxml_use_internal_errors(true);
            $dom->loadHTML($html);
            libxml_clear_errors();

            $xpath = new \DOMXPath($dom);

            // Extrair link direto para o produto
            $linkNodes = $xpath->query("//a[contains(@class, 'offer-link') or contains(@class, 'btn-offer')]");
            $directUrl = '';

            if ($linkNodes->length > 0) {
                $directUrl = $linkNodes->item(0)->getAttribute('href');
            }

            // Extrair mais detalhes se necessário
            $descriptionNodes = $xpath->query("//div[contains(@class, 'thread-description')]");
            $fullDescription = $descriptionNodes->length > 0 ?
                trim($descriptionNodes->item(0)->textContent) : '';

            return [
                'direct_url' => $directUrl,
                'full_description' => $fullDescription
            ];

        } catch (\Exception $e) {
            Log::error('Erro ao obter detalhes da promoção: ' . $e->getMessage());
            return null;
        }
    }
}
