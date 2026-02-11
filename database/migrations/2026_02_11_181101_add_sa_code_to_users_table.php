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
    Schema::table('users', function (Blueprint $table) {
        // Menyimpan kode SA (misal: SA-01, DMO, dll)
        $table->string('sa_code')->nullable()->after('role'); 
    });
}

public function down()
{
    Schema::table('users', function (Blueprint $table) {
        $table->dropColumn('sa_code');
    });
}
};
