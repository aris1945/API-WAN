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
    Schema::create('attendances', function (Blueprint $table) {
        $table->id();
        
        // Ganti foreignId jadi string (karena NIK biasanya varchar)
        $table->string('nik'); 
        
        // (Opsional) Kasih jembatan foreign key ke tabel users biar datanya kuat
        $table->foreign('nik')->references('nik')->on('users')->onDelete('cascade');

        $table->date('tanggal');
        $table->time('jam_masuk')->nullable();
        $table->time('jam_pulang')->nullable();
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
