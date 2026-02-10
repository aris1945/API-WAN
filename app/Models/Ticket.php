<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    use HasFactory;

    protected $fillable = [
    'nomor_internal', // Baru
    'nomor_sistem',   // Baru
	'site_id',
    'unit', 
	'jenis', 
	'site_name', 
	'deskripsi', 
	'petugas', 
	'status'
];
	public function logs()
{
    // Urutkan dari yang terbaru (agar di UI muncul paling atas)
    return $this->hasMany(TicketLog::class)->orderBy('created_at', 'desc');
}
}