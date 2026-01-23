<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HistoryWi extends Model
{
    protected $table = 'history_wi';

    protected $fillable = [
        'wi_document_code',
        'workcenter_induk',
        'plant_code',
        'document_date',
        'document_time',
        'posted_at',
        'sequence_number',
        'status',
    ];

    protected $casts = [
        'document_date' => 'date',
        'document_time' => 'string',   // Laravel cast time kadang tergantung driver; string aman
        'posted_at'     => 'datetime',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(HistoryWiItem::class, 'history_wi_id');
    }
}
