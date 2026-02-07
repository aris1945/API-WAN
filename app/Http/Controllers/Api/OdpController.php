<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class OdpController extends Controller
{
    public function nearest(Request $request)
    {
        // Validasi input
        $request->validate([
            'lat' => 'required|numeric',
            'long' => 'required|numeric',
        ]);

        try {
            // Token API (Gunakan token yang sama seperti sebelumnya)
            $token = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwczovL2xlbnNhLnRhY2MuaWQvYXBpL2xvZ2luIiwiaWF0IjoxNzM2MDk4NjY5LCJuYmYiOjE3MzYwOTg2NjksImp0aSI6InhFVE92eHB0ekJURW5uMkgiLCJzdWIiOiIxIiwicHJ2IjoiMjNiZDVjODk0OWY2MDBhZGIzOWU3MDFjNDAwODcyZGI3YTU5NzZmNyJ9.V_jEi2slkrkQjxHDg8xfnBhsujP_jHAfBSmOh-yqPmo';

            // Request ke External API
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Content-Type'  => 'application/json',
                'User-Agent'    => 'Dart/2.16 (dart:io)',
                'Accept-Encoding' => 'gzip'
            ])->get('https://lensa.tacc.id/api/valins/odp-coordinate', [
                'lat' => $request->lat,
                'long' => $request->long
            ]);

            if ($response->successful()) {
                $json = $response->json();

                if (isset($json['status']) && $json['status'] == true) {
                    $data = collect($json['data']);

                    // 1. Sort berdasarkan distance (terdekat)
                    $sorted = $data->sortBy('distance');

                    // 2. Filter: Ambil yang jaraknya <= 250 meter (0.25 km)
                    //    API Lensa mengembalikan distance dalam KM, jadi kita kali 1000
                    $filtered = $sorted->filter(function ($item) {
                        return ($item['distance'] * 1000) <= 250;
                    });

                    // 3. Limit: Ambil maksimal 30 data (sesuai script lama)
                    $finalData = $filtered->take(30)->values();

                    return response()->json([
                        'status' => true,
                        'count' => $finalData->count(),
                        'data' => $finalData
                    ]);
                }
            }

            return response()->json([
                'status' => false,
                'message' => 'Gagal mengambil data dari API Lensa.'
            ], 400);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Server Error: ' . $e->getMessage()
            ], 500);
        }
    }
}