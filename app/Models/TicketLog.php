<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TicketLog extends Model
{
    protected $guarded = [];

    // Relasi ke User (biar tahu siapa yang nulis log)
    public function user() {
        return $this->belongsTo(User::class);
    }
}