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
        Schema::create('gr', function (Blueprint $table) {
            // Kolom standar Laravel
            $table->id();

            // Kolom dari data SAP Anda
            $table->string('MANDT', 3)->nullable()->comment('Client');
            $table->string('LGORT', 4)->nullable()->comment('Storage location');
            $table->string('MBLNR', 10)->nullable()->comment('Number of Material Document');
            $table->string('DISPO', 3)->nullable()->comment('MRP Controller');
            $table->string('AUFNR', 12)->nullable()->comment('Order Number');
            $table->string('WERKS', 4)->nullable()->comment('Plant');
            $table->string('CHARG', 10)->nullable()->comment('Batch Number');
            $table->string('MATNR', 40)->nullable()->comment('Material Number');
            $table->string('MAKTX', 40)->nullable()->comment('Material description');
            $table->string('KDAUF', 10)->nullable()->comment('Sales Order');
            $table->string('KDPOS', 6)->nullable()->comment('Sales Order Item');
            $table->string('KUNNR', 10)->nullable()->comment('Customer Number');
            $table->string('NAME2', 30)->nullable()->comment('Name');
            $table->decimal('PSMNG', 13, 3)->nullable()->comment('Order Item Planned Total Quantity');
            $table->decimal('MENGE', 13, 3)->nullable()->comment('Quantity');
            $table->decimal('MENGEX', 13, 3)->nullable()->comment('Quantity');
            $table->decimal('MENGE_M', 13, 3)->nullable()->comment('Order Item Planned Total Quantity');
            $table->decimal('MENGE_M2', 13, 3)->nullable()->comment('Order Item Planned Total Quantity');
            $table->decimal('MENGE_M3', 13, 3)->nullable()->comment('Order Item Planned Total Quantity');
            $table->decimal('WEMNG', 13, 3)->nullable()->comment('Quantity of Goods Received for the Order Item');
            $table->string('MEINS', 3)->nullable()->comment('Base Unit of Measure');
            $table->string('LINE', 40)->nullable()->comment('System status line');
            $table->decimal('STPRS', 11, 2)->nullable()->comment('Standard price');
            $table->string('WAERS', 5)->nullable()->comment('Currency Key');
            $table->decimal('VALUE', 11, 2)->nullable()->comment('Standard price');
            $table->date('BUDAT_MKPF')->nullable()->comment('Posting Date in the Document');
            $table->date('CPUDT_MKPF')->nullable()->comment('Day On Which Accounting Document Was Entered');
            $table->integer('NODAY')->nullable()->comment('Day');
            $table->string('TXT50', 50)->nullable()->comment('Text');
            $table->decimal('NETPR', 11, 2)->nullable()->comment('Net Price');
            $table->string('WAERK', 5)->nullable()->comment('SD document currency');
            $table->decimal('VALUSX', 13, 2)->nullable()->comment('Cost in document currency');
            $table->decimal('VALUS', 13, 2)->nullable()->comment('Cost in document currency');
            $table->string('PERNR', 8)->nullable()->comment('Personnel Number');
            
            // Kolom waktu standar Laravel (created_at & updated_at)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gr');
    }
};
