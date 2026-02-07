<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Spbu;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\SpbusImport;

class SpbuController extends Controller
{
    public function index(Request $request)
    {
        $query = Spbu::query();

        if ($request->has('search')) {
            $search = $request->search;
            $query->where('nama_spbu', 'like', "%{$search}%")
                  ->orWhere('kode_spbu', 'like', "%{$search}%")
                  ->orWhere('alamat', 'like', "%{$search}%");
        }

        // --- PERUBAHAN DI SINI ---
        // Ambil jumlah per_page dari request. Default tetap 10 jika tidak diminta.
        $perPage = $request->input('per_page', 10);

        return response()->json([
            'status' => true,
            // Masukkan variabel $perPage ke dalam paginate
            'data' => $query->paginate($perPage)
        ]);
    }

    public function import(Request $request)
    {
        $request->validate(['file' => 'required|mimes:xlsx,xls,csv']);
        
        try {
            Excel::import(new SpbusImport, $request->file('file'));
            return response()->json(['status' => true, 'message' => 'Import Sukses']);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }
}