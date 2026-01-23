<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class KodeLaravel extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'kode_laravel';

    protected $fillable = [
        'laravel_code',
        'description',
        'plant',
    ];
}
