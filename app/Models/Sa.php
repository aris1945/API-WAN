<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sa extends Model
{
    protected $table = 'sas'; // Nama tabel di database
    protected $fillable = ['sa_code', 'sa_name']; // Kolom yang dapat diisi massal
}
