<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    // Ubah fillable-nya
    protected $fillable = ['nik', 'tanggal', 'jam_masuk', 'jam_pulang'];

    // Jembatan balik ke User
    public function user()
    {
        return $this->belongsTo(User::class, 'nik', 'nik');
    }
}