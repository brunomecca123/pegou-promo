<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AmazonService
{
    protected $affiliateTag;
    protected $accessKey;
    protected $secretKey;
    protected $region = 'us-east-1';

    public function __construct()
    {
        $this->affiliateTag = config('services.amazon.affiliate_tag', env('AMAZON_AFFILIATE_TAG'));
        $this->accessKey = config('services.amazon.access_key', env('AMAZON_ACCESS_KEY'));
        $this->secretKey = config('services.amazon.secret_key', env('AMAZON_SECRET_KEY'));
    }

    public function cadastrarPromocaoManual(array $dados)
    {
        // Aqui pode ser feita validação extra, tracking, etc.
        // Por enquanto, apenas retorna os dados recebidos
        return $dados;
    }

    public function generateAffiliateLink(string $originalUrl)
    {
        try {
            // Verificar se é um link da Amazon
            if (!$this->isAmazonUrl($originalUrl)) {
                return $originalUrl;
            }

            // Se não tiver tag de afiliado configurada, retorna o original
            if (empty($this->affiliateTag) || $this->affiliateTag === 'your_amazon_affiliate_tag') {
                Log::info('Tag de afiliado não configurada, retornando URL original');
                return $originalUrl;
            }

            // Extrair ASIN do URL
            $asin = $this->extractAsin($originalUrl);
            if (!$asin) {
                Log::warning('Não foi possível extrair ASIN do URL: ' . $originalUrl);
                return $originalUrl;
            }

            // Gerar link de afiliado
            $affiliateUrl = $this->buildAffiliateUrl($asin);

            Log::info('Link de afiliado gerado', [
                'original' => $originalUrl,
                'affiliate' => $affiliateUrl,
                'asin' => $asin
            ]);

            return $affiliateUrl;

        } catch (\Exception $e) {
            Log::error('Erro ao gerar link de afiliado: ' . $e->getMessage());
            return $originalUrl;
        }
    }    protected function isAmazonUrl(string $url)
    {
        $amazonDomains = [
            'amazon.com.br',
            'amazon.com',
            'amazon.co.uk',
            'amazon.de',
            'amazon.fr',
            'amazon.it',
            'amazon.es',
            'amzn.to'
        ];

        foreach ($amazonDomains as $domain) {
            if (str_contains($url, $domain)) {
                return true;
            }
        }

        return false;
    }

    protected function extractAsin(string $url)
    {
        // Padrões para extrair ASIN dos URLs da Amazon
        $patterns = [
            '/\/dp\/([A-Z0-9]{10})/',           // /dp/ASIN
            '/\/product\/([A-Z0-9]{10})/',      // /product/ASIN
            '/\/gp\/product\/([A-Z0-9]{10})/',  // /gp/product/ASIN
            '/\/exec\/obidos\/ASIN\/([A-Z0-9]{10})/', // Formato antigo
            '/\/o\/ASIN\/([A-Z0-9]{10})/',      // Outro formato
            '/asin=([A-Z0-9]{10})/',            // Query parameter
            '/\/([A-Z0-9]{10})\/ref/',          // ASIN antes de /ref/
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $url, $matches)) {
                return $matches[1];
            }
        }

        // Se não encontrar, tentar extrair de forma mais genérica
        if (preg_match('/[A-Z0-9]{10}/', $url, $matches)) {
            return $matches[0];
        }

        return null;
    }

    protected function buildAffiliateUrl(string $asin)
    {
        // Formato básico do link de afiliado da Amazon Brasil
        return "https://www.amazon.com.br/dp/{$asin}?tag={$this->affiliateTag}";
    }

    public function getProductInfo(string $asin)
    {
        try {
            // Aqui você pode implementar chamadas para a API oficial da Amazon
            // Por enquanto, vamos fazer um scraping básico
            $url = "https://www.amazon.com.br/dp/{$asin}";

            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
            ])->timeout(30)->get($url);

            if (!$response->successful()) {
                return null;
            }

            $html = $response->body();
            return $this->parseProductInfo($html);

        } catch (\Exception $e) {
            Log::error('Erro ao obter informações do produto: ' . $e->getMessage());
            return null;
        }
    }

    protected function parseProductInfo(string $html)
    {
        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML($html);
        libxml_clear_errors();

        $xpath = new \DOMXPath($dom);

        try {
            // Título do produto
            $titleNodes = $xpath->query("//span[@id='productTitle']");
            $title = $titleNodes->length > 0 ? trim($titleNodes->item(0)->textContent) : '';

            // Preço
            $priceNodes = $xpath->query("//span[contains(@class, 'a-price-whole')]");
            $price = $priceNodes->length > 0 ? trim($priceNodes->item(0)->textContent) : '';

            // Imagem principal
            $imageNodes = $xpath->query("//img[@id='landingImage']");
            $image = $imageNodes->length > 0 ? $imageNodes->item(0)->getAttribute('src') : '';

            // Rating
            $ratingNodes = $xpath->query("//span[contains(@class, 'a-icon-alt')]");
            $rating = $ratingNodes->length > 0 ? trim($ratingNodes->item(0)->textContent) : '';

            return [
                'title' => $title,
                'price' => $this->parsePrice($price),
                'image' => $image,
                'rating' => $rating
            ];

        } catch (\Exception $e) {
            Log::error('Erro ao fazer parse das informações do produto: ' . $e->getMessage());
            return null;
        }
    }

    protected function parsePrice(string $priceText)
    {
        // Remover caracteres não numéricos exceto vírgula e ponto
        $cleanPrice = preg_replace('/[^\d,.]/', '', $priceText);

        // Converter para float
        if (str_contains($cleanPrice, ',')) {
            $cleanPrice = str_replace('.', '', $cleanPrice); // Remove milhares
            $cleanPrice = str_replace(',', '.', $cleanPrice); // Troca vírgula por ponto
        }

        return is_numeric($cleanPrice) ? (float) $cleanPrice : null;
    }

    public function validateAffiliateSetup()
    {
        $errors = [];

        if (empty($this->affiliateTag)) {
            $errors[] = 'Amazon Affiliate Tag não configurado';
        }

        if (empty($this->accessKey)) {
            $errors[] = 'Amazon Access Key não configurado';
        }

        if (empty($this->secretKey)) {
            $errors[] = 'Amazon Secret Key não configurado';
        }

        return empty($errors) ? true : $errors;
    }

    public function generateShortLink(string $affiliateUrl)
    {
        try {
            // Implementar encurtador de links se necessário
            // Por enquanto, retorna o URL original
            return $affiliateUrl;

        } catch (\Exception $e) {
            Log::error('Erro ao encurtar link: ' . $e->getMessage());
            return $affiliateUrl;
        }
    }
}
