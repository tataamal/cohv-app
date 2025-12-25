<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Clean existing data to allow adding Unique Indexes
        // Data is from SAP Sync, so it can be re-fetched.
        DB::table('production_t_data4')->truncate();
        DB::table('production_t_data1')->truncate();
        DB::table('production_t_data3')->truncate();
        DB::table('production_t_data2')->truncate();
        DB::table('production_t_data')->truncate();

        // 2. Reduce column lengths to fit in Unique Index (max 3072 bytes)
        // Default string() is 255. 4 columns * 255 * 4 bytes = 4080 > 3072.
        
        // production_t_data4: WERKSX, AUFNR, RSNUM, RSPOS
        DB::statement('ALTER TABLE production_t_data4 MODIFY WERKSX VARCHAR(20)');
        DB::statement('ALTER TABLE production_t_data4 MODIFY AUFNR VARCHAR(50)');
        DB::statement('ALTER TABLE production_t_data4 MODIFY RSNUM VARCHAR(50)');
        DB::statement('ALTER TABLE production_t_data4 MODIFY RSPOS VARCHAR(20)');

        // production_t_data1: WERKSX, AUFNR, VORNR
        DB::statement('ALTER TABLE production_t_data1 MODIFY WERKSX VARCHAR(20)');
        DB::statement('ALTER TABLE production_t_data1 MODIFY AUFNR VARCHAR(50)');
        DB::statement('ALTER TABLE production_t_data1 MODIFY VORNR VARCHAR(20)');

        // production_t_data2: WERKSX, KDAUF, KDPOS
        DB::statement('ALTER TABLE production_t_data2 MODIFY WERKSX VARCHAR(20)');
        DB::statement('ALTER TABLE production_t_data2 MODIFY KDAUF VARCHAR(50)');
        DB::statement('ALTER TABLE production_t_data2 MODIFY KDPOS VARCHAR(20)');
        
        // production_t_data3: WERKSX, AUFNR (Usually fine, but good to be safe)
        DB::statement('ALTER TABLE production_t_data3 MODIFY WERKSX VARCHAR(20)');
        DB::statement('ALTER TABLE production_t_data3 MODIFY AUFNR VARCHAR(50)');

        // Helper to safely drop index
        $dropIndex = function($table, $index) {
            try {
                Schema::table($table, function (Blueprint $t) use ($index) {
                    $t->dropUnique($index);
                });
            } catch (\Exception $e) {
                // Ignore if index doesn't exist
            }
        };

        // Drop potential existing indexes from partial runs
        $dropIndex('production_t_data', 'unique_buyer_plant');
        $dropIndex('production_t_data2', 'unique_so_plant');
        $dropIndex('production_t_data3', 'unique_pro_plant');
        $dropIndex('production_t_data4', 'unique_res_plant');
        $dropIndex('production_t_data1', 'unique_op_plant');

        // 1. production_t_data (Buyer/Customer)
        // Unique: WERKSX, NAME1, KUNNR
        Schema::table('production_t_data', function (Blueprint $table) {
            $table->unique(['WERKSX', 'NAME1', 'KUNNR'], 'unique_buyer_plant');
        });

        // 2. production_t_data2 (Sales Order)
        // Unique: WERKSX, KDAUF, KDPOS
        Schema::table('production_t_data2', function (Blueprint $table) {
            $table->unique(['WERKSX', 'KDAUF', 'KDPOS'], 'unique_so_plant');
        });

        // 3. production_t_data3 (Production Order Header)
        // Unique: WERKSX, AUFNR
        Schema::table('production_t_data3', function (Blueprint $table) {
            $table->unique(['WERKSX', 'AUFNR'], 'unique_pro_plant');
        });

        // 4. production_t_data4 (Reservation)
        // Unique: WERKSX, AUFNR, RSNUM, RSPOS
        Schema::table('production_t_data4', function (Blueprint $table) {
            $table->unique(['WERKSX', 'AUFNR', 'RSNUM', 'RSPOS'], 'unique_res_plant');
        });

        // 5. production_t_data1 (Operation)
        // Unique: WERKSX, AUFNR, VORNR
        Schema::table('production_t_data1', function (Blueprint $table) {
            $table->unique(['WERKSX', 'AUFNR', 'VORNR'], 'unique_op_plant');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('production_t_data', function (Blueprint $table) {
            $table->dropUnique('unique_buyer_plant');
        });
        Schema::table('production_t_data2', function (Blueprint $table) {
            $table->dropUnique('unique_so_plant');
        });
        Schema::table('production_t_data3', function (Blueprint $table) {
            $table->dropUnique('unique_pro_plant');
        });
        Schema::table('production_t_data4', function (Blueprint $table) {
            $table->dropUnique('unique_res_plant');
        });
        Schema::table('production_t_data1', function (Blueprint $table) {
            $table->dropUnique('unique_op_plant');
        });
    }
};
