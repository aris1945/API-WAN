<?php

// app/Http/Controllers/Api/SiteController.php

namespace App\Http\Controllers\Api;

use App\Models\Site;
use App\Imports\SitesImport;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Maatwebsite\Excel\Facades\Excel;

class SiteController extends Controller
{
    // GET: api/sites
    public function index(Request $request)
    {
        // Fitur pencarian sederhana (Opsional)
        $query = Site::query();

        if ($request->has('search')) {
            $search = $request->search;
            $query->where('site_id', 'like', "%{$search}%")
                  ->orWhere('site_name', 'like', "%{$search}%")
                  ->orWhere('olt', 'like', "%{$search}%");
        }

        // Return data dengan pagination agar ringan jika data ribuan
        return response()->json([
            'status' => true,
            'data' => $query->paginate(10)
        ]);
    }

    // GET: api/sites/{site_id} -> Contoh: api/sites/SBY223
    public function show($site_id)
    {
        $site = Site::where('site_id', $site_id)->first();

        if (!$site) {
            return response()->json([
                'status' => false,
                'message' => 'Data Site tidak ditemukan'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data' => $site
        ]);
    }
    
    // POST: api/sites (Untuk input data baru)
    public function store(Request $request)
    {
        // Validasi input sesuai kebutuhan
        $validated = $request->validate([
            'site_id' => 'required|unique:sites,site_id',
            'site_name' => 'required',
            // tambahkan validasi lain sesuai kebutuhan
        ]);

        $site = Site::create($request->all());

        return response()->json([
            'status' => true,
            'message' => 'Data berhasil disimpan',
            'data' => $site
        ], 201);
    }

    public function import(Request $request)
    {
	ini_set('max_execution_time', 300); // Set timeout jadi 5 menit
    ini_set('memory_limit', '512M');
        // Validasi file harus ada dan formatnya excel/csv
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv'
        ]);

        try {
            // Proses import
            Excel::import(new SitesImport, $request->file('file'));

            return response()->json([
                'status' => true,
                'message' => 'Data Site berhasil diimport!'
            ]);
        } catch (\Exception $e) {
            // Tangkap error jika ada (misal format salah atau duplikat ID)
            return response()->json([
                'status' => false,
                'message' => 'Gagal import data: ' . $e->getMessage()
            ], 500);
        }
    }
}