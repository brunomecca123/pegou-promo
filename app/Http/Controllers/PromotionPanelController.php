<?php

namespace App\Http\Controllers;

use App\Models\Promotion;
use App\Services\TelegramService;
use Illuminate\Http\Request;

class PromotionPanelController extends Controller
{
    public function create()
    {
        return view('panel.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'url' => 'required|url',
            'image' => 'nullable|url',
        ]);

        $promotion = Promotion::create($data);
        app(TelegramService::class)->sendPromotion($promotion);

        return redirect()->back()->with('success', 'Promoção cadastrada e enviada para o Telegram!');
    }
}
