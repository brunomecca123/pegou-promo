<?php

namespace App\Services;

class AmazonService
{
    // No futuro: integração com API da Amazon
    public function cadastrarPromocaoManual(array $dados)
    {
        // Aqui pode ser feita validação extra, tracking, etc.
        // Por enquanto, apenas retorna os dados recebidos
        return $dados;
    }
}
