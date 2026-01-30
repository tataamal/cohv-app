<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HistoryPro extends Model
{
    protected $table = 'history_pro';

    protected $fillable = [
        'history_wi_item_id',
        'qty_pro',
        'status',
        'remark_text',
        'tag',
    ];

    protected $casts = [
        'qty_pro' => 'integer',
    ];

    public function wiItem(): BelongsTo
    {
        return $this->belongsTo(HistoryWiItem::class, 'history_wi_item_id');
    }
}
