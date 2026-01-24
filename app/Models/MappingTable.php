<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MappingTable extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'mapping_table';

    protected $fillable = [
        'user_sap_id',
        'kode_laravel_id',
        'mrp_id',
        'workcenter_id',
    ];

    public function userSap()
    {
        return $this->belongsTo(UserSap::class);
    }

    public function kodeLaravel()
    {
        return $this->belongsTo(KodeLaravel::class);
    }

    public function mrp()
    {
        return $this->belongsTo(MRP::class);
    }

    public function workcenter()
    {
        return $this->belongsTo(workcenter::class);
    }
}
