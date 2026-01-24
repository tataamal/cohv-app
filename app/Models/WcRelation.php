<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WcRelation extends Model
{
    use HasFactory;

    protected $fillable = [
        'wc_asal_id',
        'wc_tujuan_id',
        'status',
    ];

    public function wcAsal()
    {
        return $this->belongsTo(workcenter::class, 'wc_asal_id');
    }

    public function wcTujuan()
    {
        return $this->belongsTo(workcenter::class, 'wc_tujuan_id');
    }
}
