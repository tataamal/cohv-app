<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\WcRelation;

class workcenter extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'kode_wc',
        'werks',
        'werksx',
        'description',
    ];

    /**
     * Mendapatkan semua relasi di mana workcenter ini menjadi ASAL.
     */
    public function relasiAsal(): HasMany
    {
        return $this->hasMany(wc_relation::class, 'wc_asal_id');
    }

    /**
     * Mendapatkan semua relasi di mana workcenter ini menjadi TUJUAN.
     */
    public function relasiTujuan(): HasMany
    {
        return $this->hasMany(wc_relation::class, 'wc_tujuan_id');
    }
}
