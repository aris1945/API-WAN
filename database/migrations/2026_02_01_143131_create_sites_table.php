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
    Schema::create('sites', function (Blueprint $table) {
        $table->id();
        $table->string('site_id')->unique(); // SBY223, SBX014
        $table->string('site_name'); // SAHIDHOTELML
        $table->string('sto')->nullable(); // GBG
        $table->string('pic_wan')->nullable(); // RIZVAN
        
        // Metro Data
        $table->string('metro')->nullable(); // ME-D5-GUB
        $table->string('port_metro')->nullable(); // Eth-Trunk2
        
        // VLAN Data (String karena ada koma: "2458, 2558")
        $table->string('vlan_2g')->nullable();
        $table->string('vlan_3g')->nullable();
        $table->string('vlan_4g')->nullable();
        $table->string('vlan_oam')->nullable();
        $table->string('vlan_recti')->nullable();
        
        // OLT Data
        $table->string('olt')->nullable(); // GPON22-D5-MYR-3
        $table->string('port_olt')->nullable(); // gpon-onu_1/1/14:100

        // ONT Data
        $table->string('sn_ont')->nullable();
        $table->string('ip_olt')->nullable();
        $table->string('vlan_mgt')->nullable();
        $table->string('ip_ont')->nullable();
        $table->string('mac_bawah')->nullable();
        $table->string('lat')->nullable();
        $table->string('long')->nullable();

        // ODP Data
        $table->string('odp')->nullable();
        $table->string('latlong_odp')->nullable();
        $table->string('odc')->nullable();
        $table->string('latlong_odc')->nullable();

        // FTM Data
        $table->string('datek_ea')->nullable();
        $table->string('datek_oa')->nullable();


        
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sites');
    }
};
