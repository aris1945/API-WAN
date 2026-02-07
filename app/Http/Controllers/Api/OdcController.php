<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class OdcController extends Controller
{
    public function search(Request $request)
    {
        $odcName = $request->query('name');

        if (!$odcName) {
            return response()->json([
                'status' => false,
                'message' => 'Parameter nama ODC wajib diisi.'
            ], 400);
        }

        try {
            // Token dari kode lama Anda (Sebaiknya simpan di .env)
            $token = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwczovL2xlbnNhLnRhY2MuaWQvYXBpL2xvZ2luIiwiaWF0IjoxNzM2MDk4NjY5LCJuYmYiOjE3MzYwOTg2NjksImp0aSI6InhFVE92eHB0ekJURW5uMkgiLCJzdWIiOiIxIiwicHJ2IjoiMjNiZDVjODk0OWY2MDBhZGIzOWU3MDFjNDAwODcyZGI3YTU5NzZmNyJ9.V_jEi2slkrkQjxHDg8xfnBhsujP_jHAfBSmOh-yqPmo';

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Content-Type'  => 'application/json',
                'User-Agent'    => 'Dart/2.16 (dart:io)', // Meniru User-Agent lama
                'Accept-Encoding' => 'gzip'
            ])->get('https://lensa.tacc.id/api/valins/odc-search', [
                'name' => $odcName
            ]);

            if ($response->successful()) {
                return response()->json([
                    'status' => true,
                    'data' => $response->json()
                ]);
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Gagal mengambil data dari server Lensa.'
                ], $response->status());
            }

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Server Error: ' . $e->getMessage()
            ], 500);
        }
    }
}