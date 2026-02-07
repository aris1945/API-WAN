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
    Schema::create('spbus', function (Blueprint $table) {
        $table->id();
        $table->string('kode_spbu')->unique(); // 5460102
        $table->string('nama_spbu');           // MULYOSARI
        $table->string('ip_address')->nullable();
        $table->string('area')->nullable();    // SBS
        $table->string('so')->nullable();      // MYR
        $table->string('tipe_spbu')->nullable(); // DODO/COCO
        $table->text('alamat')->nullable();
        $table->string('latitude')->nullable();
        $table->string('longitude')->nullable(); // String dulu agar aman dari format koma
        
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('spbus');
    }
};
