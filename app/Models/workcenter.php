<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class workcenter extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'workcenters';

    protected $fillable = [
        'plant',
        'kode_wc',
        'description',
        'start_time',
        'end_time',
        'operating_time',
        'capacity',
    ];
}
