<?php

namespace App\Imports;

use App\Models\Spbu;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithStartRow;

class SpbusImport implements ToModel, WithStartRow
{
    public function startRow(): int
    {
        return 2; // Lewati Header di Baris 1
    }

    public function model(array $row)
    {
        if (!isset($row[0])) return null; // Cek Kode SPBU

        // Bersihkan Latitude (Ganti koma jadi titik, hapus ribuan separator jika ada)
        // Contoh Excel: -7,273,149 -> Database: -7.273149
        $lat = $row[7] ?? null;
        if ($lat) {
            // Hapus titik pemisah ribuan (jika ada) dan ganti koma desimal jadi titik
            $lat = str_replace('.', '', $lat); 
            $lat = str_replace(',', '.', $lat);
        }

        return Spbu::updateOrCreate(
            ['kode_spbu' => $row[0]], // Kunci Unik (Kolom A)
            [
                'nama_spbu'  => $row[1], // Kolom B
                'ip_address' => $row[2], // Kolom C
                'area'       => $row[3], // Kolom D
                'so'         => $row[4], // Kolom E
                'tipe_spbu'  => $row[5], // Kolom F
                'alamat'     => $row[6], // Kolom G
                'latitude'   => $lat,    // Kolom H (Sudah diperbaiki)
                'longitude'  => $row[8] ?? null, // Kolom I (Jika ada)
            ]
        );
    }
}