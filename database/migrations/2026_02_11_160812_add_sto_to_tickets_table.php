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
        // Menambahkan kolom 'sa' setelah kolom 'jenis'
        // Kolom ini akan menyimpan sa_code (contoh: SA-01)
        $table->string('sa')->nullable()->after('jenis');
    });
}

public function down()
{
    Schema::table('tickets', function (Blueprint $table) {
        $table->dropColumn('sa');
    });
}
};
