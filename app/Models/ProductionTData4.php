<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductionTData4 extends Model
{
    protected $table = 'production_t_data4'; // pastikan sesuai
    protected $fillable = ['MANDT',
            'RSNUM',
            'RSPOS',
            'VORNR',
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
            'WERKS',
            'BDMNG',
            'KALAB',
            'VMENG',
            'SOBSL',
            'BESKZ',
            'LTEXT',
            'LGORT',
            'OUTSREQ'];
    public function tData3()
    {
        return $this->belongsTo(ProductionTData3::class, 'AUFNR', 'AUFNR');
    }
}
