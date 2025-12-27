<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Gr extends Model
{
    use HasFactory;

    /**
     * Nama tabel database yang terhubung dengan model ini.
     *
     * @var string
     */
    protected $table = 'gr';

    /**
     * Atribut yang dapat diisi secara massal (mass assignable).
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'MANDT',
        'LGORT',
        'MBLNR',
        'DISPO',
        'AUFNR',
        'WERKS',
        'CHARG',
        'MATNR',
        'MAKTX',
        'MAT_KDAUF',
        'MAT_KDPOS',
        'KUNNR',
        'NAME2',
        'PSMNG',
        'MENGE',
        'MENGEX',
        'MENGE_M',
        'MENGE_M2',
        'MENGE_M3',
        'WEMNG',
        'MEINS',
        'LINE',
        'STPRS',
        'WAERS',
        'VALUE',
        'BUDAT_MKPF',
        'CPUDT_MKPF',
        'NODAY',
        'AUFNR2',
        'CSMG',
        'TXT50',
        'NETPR',
        'WAERK',
        'VALUSX',
        'VALUS',
        'PERNR',
        'MATNR2',
        'MAKTX2',
    ];
}
