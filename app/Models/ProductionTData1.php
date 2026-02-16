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
            'CHARG',
            'STEUS',
            'AUART',
            'MEINS',
            'MATKL',
            'PSMNG', 
            'WEMNG', 
            'MGVRG2', 
            'LMNGA', 
            'P1',
            'MENGE2',
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
            'ORDERX',
            'STATS2',
            'NAME1',
            'NETPR',
            'NETPR2',
            'WAERK',
            'ARBPL',
            'PV1',
            'PV2',
            'PV3',
            'QTY_BALANCE2',
            'SSAVZ',
            'SSSLZ',
            'SPLIM',
    ];

    public function tData3()
    {
        return $this->belongsTo(ProductionTData3::class, 'AUFNR', 'AUFNR');
    }
}
