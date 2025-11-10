<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductionTData3 extends Model
{
    protected $table = 'production_t_data3';
    protected $fillable = ['MANDT',
            'ARBPL',
            'ORDERX',
            'PWWRK', 
            'KTEXT', 
            'ARBID', 
            'VERID', 
            'KDAUF',
            'KDPOS', 
            'AUFNR',
            'NAME1',
            'KUNNR',
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
            'GSTRP',
            'GLTRP',     
            'MATFG', 
            'MAKFG',
            'CATEGORY', 
            'WERKSX', 
            'MENGE2',
            'STATS2',
            'GROES',
            'FERTH',
            'ZEINR'];
    public function tData1()
    {
        return $this->hasMany(ProductionTData1::class, 'AUFNR', 'AUFNR');
    }


    public function tData4()
    {
        return $this->hasMany(ProductionTData4::class, 'AUFNR', 'AUFNR');
    }
}
