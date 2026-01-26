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
        Schema::create('history_wi_item', function (Blueprint $table) {
            $table->id();
            $table->foreignId('history_wi_id')->constrained('history_wi')->cascadeOnDelete();
            $table->string('nik', 30);
            $table->string('aufnr', 30);
            $table->string('vornr', 10);
            $table->string('uom', 10)->nullable();
            $table->string('operator_name', 100)->nullable();
            $table->string('dispo', 10)->nullable();
            $table->unsignedInteger('kapaz')->nullable();
            $table->string('kdauf', 30)->nullable();
            $table->string('kdpos', 20)->nullable();
            $table->string('name1', 100)->nullable();
            $table->decimal('netpr', 18, 3)->nullable();
            $table->string('waerk', 10)->nullable();
            $table->date('ssavd')->nullable();
            $table->date('sssld')->nullable();
            $table->string('steus', 10)->nullable();
            $table->string('vge01', 10)->nullable();
            $table->decimal('vgw01', 18, 2)->nullable();
            $table->string('material_number', 30)->nullable()->index();
            $table->text('material_desc')->nullable();
            $table->decimal('qty_order', 18, 3)->nullable();
            $table->decimal('assigned_qty', 18, 3)->nullable();
            $table->decimal('confirmed_qty', 18, 3)->nullable()->index();
            $table->decimal('remark_qty', 18, 3)->nullable()->index();
            $table->string('remark_text', 30)->nullable()->index();
            $table->string('tag', 30)->nullable()->index();
            $table->string('workcenter_induk', 20)->nullable()->index();
            $table->string('child_workcenter', 20)->nullable()->index();
            $table->string('status_item', 30)->nullable()->index();
            $table->decimal('calculated_takt_time', 18, 2)->nullable();
            $table->longText('item_json')->nullable();
            $table->timestamps();
            $table->unique(['history_wi_id', 'nik', 'aufnr', 'vornr'], 'uq_wiitem_key');
            $table->index(['history_wi_id', 'status_item'], 'idx_wiitem_status');
            $table->index(['aufnr', 'vornr'], 'idx_wiitem_aufnr_vornr');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('history_wi_item');
    }
};
