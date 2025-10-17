<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kodes', function (Blueprint $table) {
            $table->id();
            $table->string('kode');
            $table->foreignId('sap_user_id')->constrained('sap_users')->onDelete('cascade');
            $table->string('nama_bagian');
            $table->string('kategori');
            $table->string('sub_kategori');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kodes');
    }
};
