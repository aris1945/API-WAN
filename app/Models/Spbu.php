<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Spbu extends Model
{
    use HasFactory;

    protected $fillable = [
        'kode_spbu', 'nama_spbu', 'ip_address', 
        'area', 'so', 'tipe_spbu', 'alamat', 'latitude', 'longitude'
    ];
}