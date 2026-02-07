<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class AbsensiController extends Controller
{
    public function getReport(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|string',
        ]);

        $employeeId = $request->employee_id;

        // --- 1. LOGIKA TANGGAL (Sesuai kode Apps Script Anda) ---
        $now = Carbon::now();

        if ($now->day < 16) {
            // Jika tgl 1-15: Periode = 16 Bulan Lalu s/d 15 Bulan Ini
            $startDt = $now->copy()->subMonth()->day(16);
            $endDt = $now->copy()->day(15);
        } else {
            // Jika tgl 16-31: Periode = 16 Bulan Ini s/d 15 Bulan Depan
            $startDt = $now->copy()->day(16);
            $endDt = $now->copy()->addMonth()->day(15);
        }

        // Format tanggal untuk URL (Y-n-j artinya Tahun-BulanTanpaNol-TanggalTanpaNol)
        // Contoh: 2025-7-17 (Sesuai logic concatenation di Apps Script)
        $startDateStr = $startDt->format('Y-n-j');
        $endDateStr = $endDt->format('Y-n-j');

        // Format cantik untuk response JSON
        $periodeLabel = $startDt->translatedFormat('d F Y') . ' - ' . $endDt->translatedFormat('d F Y');

        // --- 2. FETCH DATA DARI 2 PLATFORM ---
        $platforms = ['Mobile', 'Cuti'];
        $allRecords = collect([]);

        foreach ($platforms as $platform) {
            try {
                $url = "http://speakup.telkomakses.co.id:3004/report_absen/{$employeeId}/{$startDateStr}/{$endDateStr}/{$platform}";

                $response = Http::withHeaders([
                    'User-Agent' => 'Dart/3.1 (dart:io)'
                ])->timeout(10)->get($url);

                if ($response->successful()) {
                    $json = $response->json();
                    if (isset($json['data']) && is_array($json['data'])) {
                        foreach ($json['data'] as $record) {
                            // Tambahkan field keterangan platform & timestamp untuk sorting
                            $record['keterangan'] = $platform;

                            // Parse tanggal untuk sorting: "17/07/2025" + "07:55:00"
                            try {
                                $dateTimeStr = $record['present_date'] . ' ' . $record['in_dtm'];
                                $record['timestamp'] = Carbon::createFromFormat('d/m/Y H:i:s', $dateTimeStr)->timestamp;
                            } catch (\Exception $e) {
                                $record['timestamp'] = 0; // Fallback jika format error
                            }

                            $allRecords->push($record);
                        }
                    }
                }
            } catch (\Exception $e) {
                // Abaikan error per platform, lanjut ke platform berikutnya
            }
        }

        // --- 3. SORTING CHRONOLOGICALLY ---
        $sortedRecords = $allRecords->sortBy('timestamp')->values();

        return response()->json([
            'status' => true,
            'periode' => $periodeLabel,
            'total_data' => $sortedRecords->count(),
            'employee_id' => $employeeId,
            'data' => $sortedRecords
        ]);
    }
}