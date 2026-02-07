<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('history_wi_item', function (Blueprint $table) {
            if (!Schema::hasColumn('history_wi_item', 'confirmed_qty_total')) {
                $table->decimal('confirmed_qty_total', 15, 3)->default(0)->after('assigned_qty');
            }

            if (!Schema::hasColumn('history_wi_item', 'remark_qty_total')) {
                if (Schema::hasColumn('history_wi_item', 'confirmed_qty_total')) {
                    $table->decimal('remark_qty_total', 15, 3)->default(0)->after('confirmed_qty_total');
                } else {
                    $table->decimal('remark_qty_total', 15, 3)->default(0)->after('assigned_qty');
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('history_wi_item', function (Blueprint $table) {
            if (Schema::hasColumn('history_wi_item', 'remark_qty_total')) {
                $table->dropColumn('remark_qty_total');
            }
            if (Schema::hasColumn('history_wi_item', 'confirmed_qty_total')) {
                $table->dropColumn('confirmed_qty_total');
            }
        });
    }
};
