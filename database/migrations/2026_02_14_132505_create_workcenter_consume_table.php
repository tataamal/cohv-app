<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('workcenter_consume', function (Blueprint $table) {
            $table->id();
            $table->date('work_date');
            $table->unsignedBigInteger('workcenter_id');
            $table->unsignedInteger('capacity_total_sec');
            $table->unsignedInteger('capacity_used_sec')->default(0);
            $table->timestamps();
            $table->unique(['work_date', 'workcenter_id'], 'uq_wc_consume_date_wc');
            $table->index(['workcenter_id', 'work_date'], 'idx_wc_consume_wc_date');
            $table->foreign('workcenter_id', 'fk_wc_consume_workcenter')
                ->references('id')
                ->on('workcenters')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workcenter_consume');
    }
};
