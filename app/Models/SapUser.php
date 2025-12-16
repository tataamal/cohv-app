<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SapUser extends Model
{
    use HasFactory;
    protected $table = 'sap_users';
    protected $fillable = ['sap_id', 'nama'];

    public function kodes()
    {
        return $this->hasMany(Kode::class);
    }

    /**
     * Alias for kodes() to support legacy calls.
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function kode()
    {
        return $this->kodes();
    }
}
