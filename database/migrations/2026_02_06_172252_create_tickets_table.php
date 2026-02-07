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
    Schema::create('tickets', function (Blueprint $table) {
        $table->id();
        $table->string('unit');       // Unit
        $table->string('jenis');      // Jenis Tiket
        $table->string('site_name');  // Site (Disimpan namanya saja agar mudah)
        $table->text('deskripsi');    // Deskripsi Masalah
        $table->string('petugas');    // Nama Petugas
        $table->enum('status', ['Open', 'Progress', 'Closed'])->default('Open'); // Status default
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
