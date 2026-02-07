<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    // Mengambil daftar teknisi untuk dropdown
    public function getTeknisi(Request $request)
    {
        $query = User::where('role', 'teknisi');

        // Jika ada pencarian dari server side (opsional, tapi kita handle di frontend saja biar cepat)
        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $teknisi = $query->orderBy('name', 'asc')->get(['id', 'name', 'nik']);

        return response()->json([
            'status' => true,
            'data' => $teknisi
        ]);
    }
}