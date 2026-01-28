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
        Schema::table('history_wi', function (Blueprint $table) {
            $table->boolean('machining')->default(false)->after('status');
            $table->boolean('longshift')->default(false)->after('machining');
            $table->timestamp('expired_at')->nullable()->after('longshift');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('history_wi', function (Blueprint $table) {
            $table->dropColumn(['machining', 'longshift', 'expired_at']);
        });
    }
};
