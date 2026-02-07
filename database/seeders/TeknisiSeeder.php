<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class TeknisiSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Daftar Data Teknisi (Nama | NIK)
        $teknisiList = [
            ['name' => 'MOCH ROSICHOL AMIN', 'nik' => '18950788'],
            ['name' => 'JOLIAN CHRISNANTO', 'nik' => '18940509'],
            ['name' => 'RENDRA PRAHASTA', 'nik' => '19800015'],
            ['name' => 'WARSITO', 'nik' => '19780022'],
            ['name' => 'MUH HANDY S', 'nik' => '18930091'],
            ['name' => 'TRI SUTRISNO', 'nik' => '20950799'],
            ['name' => 'KERY ANAS RISKIANTO', 'nik' => '20960027'],
            ['name' => 'BAGUS ANDYANTO', 'nik' => '18950121'],
            ['name' => 'ERWANTO', 'nik' => '20960986'],
            ['name' => 'MOCH FARUQ ARDHANA', 'nik' => '20950019'],
            ['name' => 'MUHAMMAD NUR', 'nik' => '18950834'],
            ['name' => 'SUGIANTO', 'nik' => '20960020'],
            ['name' => 'DHIKA SILAHUTAMA', 'nik' => '19930040'],
            ['name' => 'MOHAMMAD REZA RIZAL', 'nik' => '20930024'],
            ['name' => 'NAUFAL HIMYAR RACHMAN', 'nik' => '19950081'],
            ['name' => 'FRANS CHORNELIUS M.', 'nik' => '18920098'],
            ['name' => 'FAYI\' SAIFUDDIN', 'nik' => '20970047'],
            ['name' => 'MOH LIAN FARDIANSAH', 'nik' => '19930169'],
            ['name' => 'FAISAL HAKQ', 'nik' => '18950377'],
            ['name' => 'FANDI EKA PURNOMO', 'nik' => '18970335'],
            ['name' => 'MUCH ARIS SETIAWAN', 'nik' => '18990339'],
        ];

        foreach ($teknisiList as $data) {
            User::updateOrCreate(
                ['nik' => $data['nik']], // Cek berdasarkan NIK agar tidak duplikat
                [
                    'name' => $data['name'],
                    'role' => 'teknisi',
                    // Password diset sama dengan NIK & di-hash
                    'password' => Hash::make($data['nik']), 
                    'email' => null, // Email dikosongkan karena login pakai NIK
                ]
            );
        }
    }
}