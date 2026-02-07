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
        $table->string('nik')->unique()->after('name'); // NIK harus unik
        $table->enum('role', ['admin', 'helpdesk', 'teknisi'])->default('teknisi')->after('nik');
        // Email kita buat nullable (opsional) karena login pakai NIK
        $table->string('email')->nullable()->change(); 
    });
}

public function down()
{
    Schema::table('users', function (Blueprint $table) {
        $table->dropColumn(['nik', 'role']);
        $table->string('email')->nullable(false)->change();
    });
}
};
