<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class TelegramService
{
    public static function sendMessage($chatId, $message)
    {
        $token = env('TELEGRAM_BOT_TOKEN');
        
        if (!$chatId || !$token) return;

        try {
            Http::post("https://api.telegram.org/bot{$token}/sendMessage", [
                'chat_id' => $chatId,
                'text'    => $message,
                'parse_mode' => 'HTML' // Agar bisa pakai bold/italic
            ]);
        } catch (\Exception $e) {
            // Log error jika gagal, tapi jangan hentikan aplikasi
            \Log::error("Gagal kirim Telegram: " . $e->getMessage());
        }
    }
}