<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Ticket;

class StoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $stoList = [
            'IJK',
            'RKT',
            'GBG',
            'MYR',
            'DMO',
        ];

        foreach ($stoList as $sto) {
            Ticket::updateOrCreate(
                ['name' => $stoList], // Cek berdasarkan nama agar tidak duplikat
                ['name' => $stoList]  // Data yang akan diinsert atau diupdate
            );
        }
    }
}
