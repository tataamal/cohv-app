<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MRP extends Model
{
    use HasFactory;
    protected $table = 'mrps';
    protected $fillable = ['mrp'];


    public function kode()
    {
        return $this->belongsTo(Kode::class, 'kode');
    }
}
