<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::table('tickets', function (Blueprint $table) {
        // Tiket Internal (Wajib diisi, misal: NOC-2026-001)
        $table->string('nomor_internal')->after('id')->nullable(false); 
        
        // Tiket Sistem (Boleh kosong, diisi nanti saat ada laporan pelanggan)
        $table->string('nomor_sistem')->after('nomor_internal')->nullable(); 
    });
}

public function down()
{
    Schema::table('tickets', function (Blueprint $table) {
        $table->dropColumn(['nomor_internal', 'nomor_sistem']);
    });
}
};
