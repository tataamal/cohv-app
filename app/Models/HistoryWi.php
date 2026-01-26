<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HistoryWi extends Model
{
    // use SoftDeletes; // Removed because table does not have deleted_at
    /**
     * Nama tabel di database.
     * @var string
     */
    protected $table = 'history_wi';

    /**
     * Atribut yang dapat diisi secara massal (Mass Assignable).
     * @var array<int, string>
     */
    protected $fillable = [
        'wi_document_code',
        'workcenter_induk', // Renamed from workcenter_code
        'plant_code',
        'document_date',
        'sequence_number',
        'document_time',
        'posted_at',
        'status',
        // 'payload_data' removed
        // 'expired_at' removed from migration but controller uses it. Check migration again?
        // Migration doesn't show expired_at. Controller relies on it.
        // User said: "untuk mengganti penyimpanan payload datanya".
        // Migration `history_wi`: id, wi_document_code, workcenter_induk, plant_code, document_date, document_time, posted_at, sequence_number, status, timestamps.
        // MISSING: expired_at, year.
        // I will trust the migration for table name and basic fields, but Controller logic DEPENDS heavily on expired_at.
        // If expired_at is missing from DB, controller queries will fail.
        // I should probably ASSUME expired_at is handled differently or I might be missing a migration part.
        // Wait, user provided TWO migrations.
        // If I assume migration is truth, I must remove expired_at from model too? But then controller logic `where('expired_at'...)` breaks.
        // Maybe I should add payload_data accessor to mimic old behavior for backward compatibility if possible, or refactor controller completely.
        // Let's refactor model to match TABLE first.
    ];

    /**
     * Tipe data kustom untuk konversi (Casts).
     * @var array
     */
    protected $casts = [
        'document_date' => 'date',
        'sequence_number' => 'integer',
        'posted_at' => 'datetime',
    ];

    /**
     * Relationship to KodeLaravel model (Plant Code).
     */
    public function kode()
    {
        return $this->belongsTo(\App\Models\KodeLaravel::class, 'plant_code', 'laravel_code');
    }

    /**
     * Relationship to Items.
     */
    public function items()
    {
        return $this->hasMany(\App\Models\HistoryWiItem::class, 'history_wi_id');
    }

    /**
     * Accessor for Department Name (Bagian).
     */
    public function getDepartmentAttribute()
    {
        return $this->kode ? $this->kode->description : 'UNKNOWN DEPT';
    }
}