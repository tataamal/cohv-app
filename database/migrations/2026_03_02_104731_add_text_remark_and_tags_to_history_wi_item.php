<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('history_wi_item', function (Blueprint $table) {
            $table->text('remark_text')->nullable();
            $table->string('tag')->nullable();
        });

        // Schema::dropIfExists('history_pro');
    }

    public function down(): void
    {
        // Schema::create('history_pro', function (Blueprint $table) {
        //     $table->id();
        //     $table->foreignId('history_wi_item_id')
        //         ->constrained('history_wi_item')
        //         ->cascadeOnDelete();

        //     $table->integer('qty_pro');
        //     $table->enum('status', ['confirmasi', 'remark'])->nullable();
        //     $table->text('remark_text')->nullable();
        //     $table->string('tag')->nullable();
        //     $table->timestamps();
        // });
        Schema::table('history_wi_item', function (Blueprint $table) {
            $table->dropColumn(['remark_text', 'tag']);
        });
    }
};