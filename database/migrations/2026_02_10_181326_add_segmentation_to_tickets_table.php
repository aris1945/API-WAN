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
            // Pastikan 3 baris ini ada!
            $table->string('odp')->nullable()->after('deskripsi');
            $table->string('odc')->nullable()->after('odp');
            $table->string('ftm')->nullable()->after('odc');
        });
    }

    public function down()
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn(['odp', 'odc', 'ftm']);
        });
    }
};
