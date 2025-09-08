<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductionTData2 extends Model
{
    protected $table = 'production_t_data2'; // pastikan sesuai
    protected $fillable = ['MANDT',
           'KDAUF', 
           'KDPOS', 
           'MATFG', 
           'MAKFG','EDATU','WERKSX'];
    public function tData3()
    {
        return $this->hasMany(ProductionTData3::class, 'KDAUF', 'KDAUF')
            ->whereColumn('production_t_data3.KDPOS', 'production_t_data2.KDPOS');
    }
}
