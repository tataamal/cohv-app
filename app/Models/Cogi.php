<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder; // Diperlukan untuk Query Scope
use Carbon\Carbon;

class Cogi extends Model
{
    use HasFactory;
    /**
     * Nama tabel yang terhubung dengan model ini.
     */
    protected $table = 'tb_cogi';

    /**
     * Atribut yang tidak boleh diisi secara massal.
     */
    protected $guarded = ['id'];

    /**
     * Casting tipe data untuk atribut model.
     * Mengubah kolom tertentu menjadi tipe data yang sesuai secara otomatis.
     */
    protected $casts = [
        'BUDAT' => 'date', // Otomatis mengubah string tanggal menjadi objek Carbon
        'PSMNG' => 'float',
        'WEMNG' => 'float',
        'ERFMG' => 'float',
        'MENGE' => 'float',
    ];

    // --- QUERY SCOPES ---
    // Shortcut untuk query yang sering digunakan agar controller lebih bersih.

    /**
     * Scope untuk memfilter data berdasarkan plant (DWERK).
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string $plant
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOfPlant(Builder $query, string $plant): Builder
    {
        return $query->where('DWERK', $plant);
    }

    /**
     * Scope untuk mencari data berdasarkan kata kunci di pesan error.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string $keyword
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeHasError(Builder $query, string $keyword): Builder
    {
        return $query->where('PESAN_ERROR', 'like', '%' . $keyword . '%');
    }

    /**
     * Scope untuk memfilter data dalam rentang tanggal tertentu.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string $startDate
     * @param  string $endDate
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForDateRange(Builder $query, string $startDate, string $endDate): Builder
    {
        return $query->whereBetween('BUDAT', [$startDate, $endDate]);
    }

    // --- ACCESSORS ---
    // Memformat atribut saat diambil dari model.

    /**
     * Accessor untuk mendapatkan deskripsi material lengkap.
     * Atribut ini tidak ada di database, tapi dibuat secara dinamis.
     *
     * @return string
     */
    public function getMaterialFullDescriptionAttribute(): string
    {
        // Menggabungkan kode material dan deskripsinya
        return "[{$this->MATNR}] {$this->MAKTX}";
    }
}
