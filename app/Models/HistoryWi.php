<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class HistoryWi extends Model
{
    use SoftDeletes;
    /**
     * Nama tabel di database.
     * @var string
     */
    protected $table = 'db_history_wi';

    /**
     * Atribut yang dapat diisi secara massal (Mass Assignable).
     * @var array<int, string>
     */
    protected $fillable = [
        'wi_document_code',
        'workcenter_code',
        'plant_code',
        'document_date',
        'sequence_number',
        'payload_data',
        'document_time',
        'expired_at',
        'deleted_at'
    ];

    /**
     * Tipe data kustom untuk konversi (Casts).
     * Ini memastikan 'payload_data' otomatis dikonversi dari JSON string ke PHP array/object.
     * @var array
     */
    protected $casts = [
        'document_date' => 'date',
        'sequence_number' => 'integer',
        'payload_data' => 'array',
        'expired_at' => 'datetime',
    ];
}