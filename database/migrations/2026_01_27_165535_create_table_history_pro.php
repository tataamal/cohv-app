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
        Schema::create('history_pro', function (Blueprint $table) {
            $table->id();
            $table->foreignId('history_wi_item_id')->constrained('history_wi_item')->cascadeOnDelete();
            $table->integer('qty_pro')->notNull();
            $table->enum('status', ['confirmasi', 'remark'])->nullable();
            $table->text('remark_text')->nullable();
            $table->string('tag')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('table_history_pro');
    }
};
