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
        'nama',
        'total_time_wi',
        'kode_laravel', // diambil dari plant_code
        'tag',
        'plant',
    ];

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = true;
}
