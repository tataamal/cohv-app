<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class WcCompatibility extends Model
{
    use HasFactory;

    /**
     * Nama tabel yang terhubung dengan model ini.
     *
     * @var string
     */
    protected $table = 'wc_compatibilities';

    /**
     * Atribut yang dapat diisi secara massal (mass assignable).
     * Ini penting agar seeder kita bisa berfungsi.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'wc_asal',
        'wc_tujuan',
        'status',
        'plant',
    ];
}
