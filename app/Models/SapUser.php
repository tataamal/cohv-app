<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SapUser extends Model
{
    use HasFactory;
    protected $table = 'sap_users';
    protected $fillable = ['sap_id', 'nama'];

    public function kode()
    {
        return $this->hasOne(Kode::class);
    }
}
