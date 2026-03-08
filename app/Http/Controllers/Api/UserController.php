<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    // Mengambil daftar teknisi untuk dropdown
    public function getTeknisiActive() 
    {
        $hariIni = date('Y-m-d');
        
        // Pakai metode JOIN yang lebih kejam dan barbar
        $teknisi = \App\Models\User::select('users.*')
            ->join('attendances', 'users.nik', '=', 'attendances.nik')
            ->where('attendances.tanggal', $hariIni)
            ->whereNotNull('attendances.jam_masuk')
            ->whereNull('attendances.jam_pulang')
            ->where('users.role', 'teknisi') // <--- GUE MATIIN DULU SEMENTARA BIAR NGGAK ERROR ROLE
            ->groupBy('users.nik') // Jaga-jaga biar namanya gak dobel kalau dia absen 2 kali
            ->get();
            
        return response()->json(['data' => $teknisi], 200);
    }
}