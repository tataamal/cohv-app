<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
        'wc_induk',
        'nama_wc_induk',
        'workcenter',
        'nama_workcenter',
        'kode_laravel',
        'plant',
    ];
}