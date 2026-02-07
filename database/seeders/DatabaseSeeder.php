<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
 // Jangan lupa import

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    

public function run()
{
    // Admin
    User::create([
        'name' => 'Administrator',
        'nik' => '1001',
        'role' => 'admin',
        'password' => bcrypt('password123'),
    ]);

    // Helpdesk
    User::create([
        'name' => 'Helpdesk',
        'nik' => '2001',
        'role' => 'helpdesk',
        'password' => bcrypt('password123'),
    ]);

    // Teknisi
    User::create([
        'name' => 'Teknisi',
        'nik' => '3001',
        'role' => 'teknisi',
        'password' => bcrypt('password123'),
    ]);
}
}
