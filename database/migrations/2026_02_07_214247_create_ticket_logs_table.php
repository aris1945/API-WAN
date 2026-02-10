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
    Schema::create('ticket_logs', function (Blueprint $table) {
        $table->id();
        $table->foreignId('ticket_id')->constrained()->onDelete('cascade');
        $table->foreignId('user_id'); // Siapa yang update (Teknisi/Admin)
        $table->string('status'); // Status pada saat log ini dibuat (OTW, Progress, Closed)
        $table->text('deskripsi'); // Catatan pengerjaan
        $table->string('image_path')->nullable(); // Foto evident
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket_logs');
    }
};
