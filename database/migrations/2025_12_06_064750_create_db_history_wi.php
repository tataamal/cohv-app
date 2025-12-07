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
        Schema::create('db_history_wi', function (Blueprint $table) {
            $table->id();
            $table->string('wi_document_code', 20)->unique();
            $table->string('workcenter_code', 10);
            $table->string('plant_code', 4);
            $table->date('document_date');
            $table->unsignedSmallInteger('sequence_number');
            $table->json('payload_data');
            $table->index(['workcenter_code', 'document_date']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('db_history_wi');
    }
};
