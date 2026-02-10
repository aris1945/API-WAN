<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GoogleSheetService
{
    public static function send($ticket, $action = 'create')
    {
        $url = env('GOOGLE_SCRIPT_URL');

        if (!$url) return;

        try {
            // Data yang dikirim ke Google Script
            $payload = [
                'secret_key' => 'RAHASIA_DAPUR_KITA_123', // Harus sama dengan di GAS
                'action' => $action, // 'create' atau 'update'
                'ticket' => [
                    'id' => $ticket->id,
                    'nomor_internal' => $ticket->nomor_internal,
                    'unit' => $ticket->unit,
                    'site_id' => $ticket->site_id,
                    'site_name' => $ticket->site_name,
                    'deskripsi' => $ticket->deskripsi,
                    'status' => $ticket->status,
                    'petugas' => $ticket->petugas,
                    'odp' => $ticket->odp,
                    'odc' => $ticket->odc,
                    'ftm' => $ticket->ftm,
                ]
            ];

            // Kirim POST Request
            Http::post($url, $payload);
        } catch (\Exception $e) {
            Log::error("Gagal kirim ke Google Sheet: " . $e->getMessage());
        }
    }
}
