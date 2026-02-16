<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkcenterConsume extends Model
{
    protected $table = 'workcenter_consume';

    protected $fillable = [
        'work_date',
        'workcenter_id',
        'capacity_total_sec',
        'capacity_used_sec',
    ];

    protected $casts = [
        'work_date' => 'date',
        'capacity_total_sec' => 'integer',
        'capacity_used_sec' => 'integer',
    ];

    public function workcenter(): BelongsTo
    {
        return $this->belongsTo(Workcenter::class, 'workcenter_id');
    }

    public function remainingSec(): int
    {
        return max(0, (int)$this->capacity_total_sec - (int)$this->capacity_used_sec);
    }
}
