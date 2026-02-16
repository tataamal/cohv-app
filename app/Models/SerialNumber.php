<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SerialNumber extends Model
{
    protected $table = 'serial_number';
    protected $fillable = [
        'so',
        'item',
        'serial_number',
        'gi_painting_date',
        'gr_painting_date',
        'gi_packing_date',
        'gr_packing_date',
    ];
}

