<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductionTData1 extends Model
{
    protected $table = 'production_t_data1';
    protected $fillable = ['MANDT',
            'ARBPL',
            'PWWRK', 
            'KTEXT', 
            'WERKSX', 
            'ARBID',
            'KAPID',
            'KAPAZ', 
            'VERID', 
            'KDAUF',
            'KDPOS', 
            'AUFNR',
            'PLNUM', 
            'STATS', 
            'DISPO',
            'MATNR', 
            'MTART', 
            'MAKTX',
            'VORNR',
            'STEUS',
            'AUART',
            'MEINS',
            'MATKL',
            'PSMNG', 
            'WEMNG', 
            'MGVRG2', 
            'LMNGA', 
            'P1',
            'MENG2',
            'VGW01',
            'VGE01', 
            'CPCTYX',
            'DTIME',   
            'DDAY',    
            'SSSLD',   
            'SSAVD',     
            'MATFG', 
            'MAKFG',
            'CATEGORY', 
            'MENGE2',
            'ORDERX',
            'STATS2',
            'ARBPL',
            'QTY_BLALAMCE2',
            'PV1',
            'PV2',
            'PV3',
            'QTY_BALANCE2 '
    ];

    public function tData3()
    {
        return $this->belongsTo(ProductionTData3::class, 'AUFNR', 'AUFNR');
    }
}
