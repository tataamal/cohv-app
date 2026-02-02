<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Workcenter;

class WcRelation extends Model
{
    use HasFactory;

    /**
     * Nama tabel yang digunakan oleh model.
     *
     * @var string
     */
    protected $table = 'wc_relations';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'wc_asal_id',
        'wc_tujuan_id',
        'status',
    ];

    /**
     * Mendapatkan data workcenter ASAL dari relasi ini.
     */
    public function wcAsal(): BelongsTo
    {
        return $this->belongsTo(Workcenter::class, 'wc_asal_id');
    }

    /**
     * Mendapatkan data workcenter TUJUAN dari relasi ini.
     */
    public function wcTujuan(): BelongsTo
    {
        return $this->belongsTo(Workcenter::class, 'wc_tujuan_id');
    }
}
