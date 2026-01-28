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
        'workcenter_induk', // Renamed from workcenter_code
        'plant_code',
        'document_date',
        'sequence_number',
        'document_time',
        'posted_at',
        'status',
        'machining',
        'longshift',
        'expired_at',
    ];

    /**
     * Tipe data kustom untuk konversi (Casts).
     * @var array
     */
    protected $casts = [
        'document_date' => 'date',
        'sequence_number' => 'integer',
        'posted_at' => 'datetime',
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

    public function getProSummaryAttribute()
    {
        $details = $this->items->map(function($item) {
            $assigned = floatval($item->assigned_qty);
            $confirmed = floatval($item->confirmed_qty ?? 0);
            $remarkQty = floatval($item->remark_qty ?? 0);
            
            // Progress Calculation
            $progressPct = 0;
            if ($assigned > 0) {
                $progressPct = ($confirmed / $assigned) * 100;
            }

            return [
                'aufnr' => $item->aufnr,
                'vornr' => $item->vornr,
                'nik' => $item->nik,
                'name' => $item->operator_name,
                'material' => $item->material_desc ?? $item->material_number,
                'material_desc' => $item->material_desc,
                'material_number' => $item->material_number,
                'assigned_qty' => $assigned,
                'qty_order' => floatval($item->qty_order),
                'uom' => $item->uom,
                'vgw01' => floatval($item->vgw01),
                'vge01' => $item->vge01,
                'item_mins' => floatval($item->calculated_takt_time),
                'confirmed_qty' => $confirmed,
                'remark_qty' => $remarkQty,
                'remark' => $item->remark_text, // legacy
                'remark_history' => [], // Decode if needed? Currently null in table
                'progress_pct' => $progressPct,
                'machining' => $item->machining,
                'longshift' => $item->longshift,
                'is_machining' => $item->machining, // Alias
                'is_longshift' => $item->longshift,
            ];
        })->toArray();

        return ['details' => $details];
    }

    public function getCapacityInfoAttribute()
    {
         $usedMins = $this->items->sum('calculated_takt_time');
         $maxMins = $this->items->max('kapaz') ?? 0;
         $pct = ($maxMins > 0) ? ($usedMins / $maxMins) * 100 : 0;

         return [
             'max_mins' => $maxMins,
             'used_mins' => $usedMins,
             'percentage' => $pct
         ];
    }
}