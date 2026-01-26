<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
        'confirmed_qty',
        'workcenter_induk',
        'child_workcenter',
        'status_item',
        'calculated_takt_time',
        'item_json',
    ];

    protected $casts = [
        'ssavd' => 'date',
        'sssld' => 'date',
        'netpr' => 'decimal:3',
        'vgw01' => 'decimal:2',
        'qty_order' => 'decimal:3',
        'assigned_qty' => 'decimal:3',
        'confirmed_qty' => 'decimal:3',
        'calculated_takt_time' => 'decimal:2',
    ];

    public function wi(): BelongsTo
    {
        return $this->belongsTo(HistoryWi::class, 'history_wi_id');
    }
}
