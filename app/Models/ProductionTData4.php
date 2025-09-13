<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductionTData4 extends Model
{
    protected $table = 'production_t_data4'; // pastikan sesuai
    protected $fillable = ['MANDT',
            'RSNUM',
            'RSPOS',
            'KDAUF', 
            'KDPOS', 
            'AUFNR',
            'PLNUM',
            'STATS',
            'DISPO',
            'MATNR',
            'MAKTX', 
            'MEINS',
            'BAUGR',
            'WERKSX',
            'BDMNG',
            'KALAB',
            'SOBSL',
            'BESKZ',
            'LTEXT'];
    public function tData3()
    {
        return $this->belongsTo(ProductionTData3::class, 'AUFNR', 'AUFNR');
    }
}
