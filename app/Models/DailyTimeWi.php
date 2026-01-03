<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DailyTimeWi extends Model
{
    /**
     * The connection name for the model.
     *
     * @var string|null
     */
    protected $connection = 'mysql_person';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'daily_time_wi';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'tanggal',
        'nik',
        'total_time_wi',
        'kode_laravel'
    ];

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false; // Assuming no created_at/updated_at unless specified
}
