<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\workcenter;

class WorkcenterMapping extends Model
{
    use HasFactory;

    // Menentukan nama tabel yang sesuai dengan migration
    protected $table = 'workcenter_mappings';

    /**
     * Atribut yang dapat diisi secara massal (mass assignable).
     * Pastikan semua kolom non-timestamps ada di sini.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'wc_induk_id',
        'wc_anak_id',
        'kode_laravel_id',
    ];

    public function parentWorkcenter()
    {
        return $this->belongsTo(workcenter::class, 'wc_induk_id');
    }

    public function childWorkcenter()
    {
        return $this->belongsTo(workcenter::class, 'wc_anak_id');
    }

    public function kodeLaravel()
    {
        return $this->belongsTo(KodeLaravel::class, 'kode_laravel_id');
    }
}