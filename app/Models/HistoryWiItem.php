<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HistoryWiItem extends Model
{
    protected $table = 'history_wi_item';

    protected $fillable = [
        'history_wi_id',
        'nik',
        'aufnr',
        'vornr',
        'uom',
        'operator_name',
        'dispo',
        'kapaz',
        'kdauf',
        'kdpos',
        'name1',
        'netpr',
        'waerk',
        'ssavd',
        'sssld',
        'steus',
        'vge01',
        'vgw01',
        'material_number',
        'material_desc',
        'qty_order',
        'assigned_qty',
        'confirmed_qty_total',
        'remark_qty_total',
        'parent_wc',
        'child_wc',
        'status',
        'machining',
        'longshift',
        'calculated_takt_time',
        'stats',
    ];

    protected $casts = [
        'ssavd' => 'date',
        'sssld' => 'date',
        'netpr' => 'decimal:3',
        'vgw01' => 'decimal:2',
        'qty_order' => 'decimal:3',
        'assigned_qty' => 'decimal:3',
        'confirmed_qty_total' => 'decimal:3',
        'remark_qty_total'    => 'decimal:3',
        'calculated_takt_time' => 'decimal:2',
    ];

    public function wi(): BelongsTo
    {
        return $this->belongsTo(HistoryWi::class, 'history_wi_id');
    }

    public function pros()
    {
        return $this->hasMany(\App\Models\HistoryPro::class, 'history_wi_item_id');
    }
}
