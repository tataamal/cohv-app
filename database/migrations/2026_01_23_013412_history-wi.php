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
        Schema::create('history_wi', function (Blueprint $table) {
            $table->id();
            $table->string('wi_document_code', 30)->unique();
            $table->string('workcenter_induk', 20)->index();
            $table->string('plant_code', 4)->nullable()->index();
            $table->date('document_date')->nullable()->index();
            $table->time('document_time')->nullable();
            $table->dateTime('posted_at')->nullable()->index();
            $table->unsignedInteger('sequence_number')->nullable()->index();
            $table->string('status', 100)->nullable()->index();
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('history_wi');
    }
};
