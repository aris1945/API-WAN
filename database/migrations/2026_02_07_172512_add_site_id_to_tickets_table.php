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
        // Menambah kolom site_id setelah site_name
        $table->string('site_id')->nullable()->after('site_name');
    });
}

public function down()
{
    Schema::table('tickets', function (Blueprint $table) {
        $table->dropColumn('site_id');
    });
}
};
