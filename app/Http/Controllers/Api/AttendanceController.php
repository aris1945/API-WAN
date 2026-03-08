<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Attendance; // Panggil modelnya

class AttendanceController extends Controller
{
    // Nah, naruh public function itu WAJIB di dalem kurung kurawal class ini Bos!
    public function absenMasuk(Request $request) 
    {
        $user = auth()->user(); 
        $hariIni = date('Y-m-d');
        
        // Cek dia udah absen belum hari ini (Pakai NIK)
        $cekAbsen = Attendance::where('nik', $user->nik)->where('tanggal', $hariIni)->first();
        
        if($cekAbsen) {
            return response()->json(['message' => 'Lu udah absen hari ini Bos!'], 400);
        }

        // Insert absen masuk pakai NIK + kordinat (kalau dari request lu sebelumnya mau nyimpen Lat/Lng sekalian)
        Attendance::create([
            'nik' => $user->nik,
            'tanggal' => $hariIni,
            'jam_masuk' => date('H:i:s'),
            // 'latitude' => $request->latitude, // Buka komen ini kalau di database lu udah ada kolomnya
            // 'longitude' => $request->longitude // Buka komen ini kalau di database lu udah ada kolomnya
        ]);

        return response()->json(['message' => 'Mantap, absen sukses! Selamat bekerja!'], 200);
    }

    // Fungsi buat ngecek status tombol
    public function cekStatus(Request $request) 
    {
        $user = auth()->user();
        $hariIni = date('Y-m-d');
        
        $absen = Attendance::where('nik', $user->nik)->where('tanggal', $hariIni)->first();
        
        if (!$absen) {
            return response()->json(['status' => 'belum_absen']);
        } elseif ($absen && $absen->jam_pulang == null) {
            return response()->json(['status' => 'sudah_masuk']);
        } else {
            return response()->json(['status' => 'sudah_pulang']);
        }
    }

    // Fungsi buat eksekusi absen pulang
    public function absenPulang(Request $request) 
    {
        $user = auth()->user();
        $hariIni = date('Y-m-d');
        
        $absen = Attendance::where('nik', $user->nik)->where('tanggal', $hariIni)->first();
        
        if (!$absen) {
            return response()->json(['message' => 'Lu belum absen masuk jing!'], 400);
        }
        if ($absen->jam_pulang != null) {
            return response()->json(['message' => 'Lu udah pulang tadi!'], 400);
        }

        $absen->update([
            'jam_pulang' => date('H:i:s'),
            // 'latitude_pulang' => $request->latitude, // (Opsional kalau lu nyimpen kordinat pulang)
            // 'longitude_pulang' => $request->longitude
        ]);

        return response()->json(['message' => 'Mantap, hati-hati di jalan Bos!'], 200);
    }
}