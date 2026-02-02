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
            $table->dropColumn(['confirmed_qty', 'remark_text', 'remark_qty','item_json']);
            $table->renameColumn('workcenter_induk', 'parent_wc');
            $table->renameColumn('child_workcenter', 'child_wc');
            $table->renameColumn('status_item', 'status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('history_wi_item', function (Blueprint $table) {
            $table->decimal('confirmed_qty', 15, 3)->nullable()->after('assigned_qty');
            $table->text('remark_text')->nullable()->after('confirmed_qty');
            $table->decimal('remark_qty', 15, 3)->nullable()->after('remark_text');
            $table->json('item_json')->nullable()->after('remark_qty');
            $table->renameColumn('parent_wc', 'workcenter_induk');
            $table->renameColumn('child_wc', 'child_workcenter');
            $table->renameColumn('status', 'status_item');
        });
    }
};
