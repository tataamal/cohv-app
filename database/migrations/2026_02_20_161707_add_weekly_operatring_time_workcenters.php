<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('workcenters', function (Blueprint $table) {
            // Tambah kolom baru (VARCHAR)
            $table->string('operating_time_1', 32)->nullable()->after('end_time');
            $table->string('operating_time_2', 32)->nullable()->after('operating_time_1');
            $table->string('operating_time_3', 32)->nullable()->after('operating_time_2');
            $table->string('operating_time_4', 32)->nullable()->after('operating_time_3');
            $table->string('operating_time_5', 32)->nullable()->after('operating_time_4');
            $table->string('operating_time_6', 32)->nullable()->after('operating_time_5');
            $table->string('operating_time_7', 32)->nullable()->after('operating_time_6');

            // Hapus kolom lama
            if (Schema::hasColumn('workcenters', 'operating_time')) {
                $table->dropColumn('operating_time');
            }
        });
    }

    public function down(): void
    {
        Schema::table('workcenters', function (Blueprint $table) {
            // Balikin kolom lama
            $table->string('operating_time', 32)->nullable()->after('end_time');

            // Hapus kolom baru
            $table->dropColumn([
                'operating_time_1',
                'operating_time_2',
                'operating_time_3',
                'operating_time_4',
                'operating_time_5',
                'operating_time_6',
                'operating_time_7',
            ]);
        });
    }
};