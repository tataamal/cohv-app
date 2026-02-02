<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\SoftDeletes;

class HistoryWi extends Model
{
    use SoftDeletes;
    /**
     * Nama tabel di database.
     * @var string
     */
    protected $table = 'history_wi';

    /**
     * Atribut yang dapat diisi secara massal (Mass Assignable).
     * @var array<int, string>
     */
    protected $fillable = [
        'wi_document_code',
        'workcenter',
        'plant_code',
        'document_date',
        'document_time',
        'expired_at',
        'sequence_number',
        'status',
        'machining',
        'longshift',
    ];

    /**
     * Tipe data kustom untuk konversi (Casts).
     * @var array
     */
    protected $casts = [
        'document_date' => 'date:Y-m-d',
        'expired_at'    => 'datetime',
        'document_time' => 'string',
        'machining'     => 'integer',
        'longshift'     => 'integer',
    ];

    public function kode()
    {
        return $this->belongsTo(\App\Models\KodeLaravel::class, 'plant_code', 'laravel_code');
    }

    public function items()
    {
        return $this->hasMany(\App\Models\HistoryWiItem::class, 'history_wi_id');
    }

    public function getDepartmentAttribute()
    {
        return $this->kode ? $this->kode->description : 'UNKNOWN DEPT';
    }
}