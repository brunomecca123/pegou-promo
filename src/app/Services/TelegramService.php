<?php

namespace App\Services;

use App\Models\Promotion;
use Illuminate\Support\Facades\Http;

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
        $text = "<b>{$promotion->title}</b>\n";
        if ($promotion->description) {
            $text .= "{$promotion->description}\n";
        }
        if ($promotion->url) {
            $text .= "<a href='{$promotion->url}'>Ver promoção</a>";
        }

        $response = Http::post("https://api.telegram.org/bot{$this->token}/sendMessage", [
            'chat_id' => $this->channelId,
            'text' => $text,
            'parse_mode' => 'HTML',
            'disable_web_page_preview' => false,
        ]);

        return $response->successful();
    }
}
