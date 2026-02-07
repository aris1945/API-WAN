<?php

// app/Models/Site.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Site extends Model
{
    use HasFactory;

    protected $fillable = [
        'site_id',
        'site_name',
        'sto',
        'pic_wan',
        'metro',
        'port_metro',
        'vlan_2g',
        'vlan_3g',
        'vlan_4g',
        'vlan_oam',
        'vlan_recti',
        'olt',
        'port_olt',
        'sn_ont',
        'ip_olt',
        'vlan_mgt',
        'ip_ont',
        'mac_bawah',
        'lat',
        'long',
        'odp',
        'latlong_odp',
        'odc',
        'latlong_odc',
        'datek_ea',
        'datek_oa'
    ];
}