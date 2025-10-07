<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Kode extends Model
{
    use HasFactory;
    protected $table = 'kodes';
    protected $fillable = ['kode','sap_user_id', 'nama_bagian', 'kategori'];

    public function sapUser()
    {
        return $this->belongsTo(SapUser::class, 'sap_user_id');
    }
    public function mrps()
    {
        // Argumen kedua ('kode_id') adalah nama foreign key di tabel 'mrps'.
        return $this->hasMany(MRP::class, 'kode_id');
    }
}
