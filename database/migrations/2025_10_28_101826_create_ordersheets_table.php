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
        Schema::create('ordersheets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_user')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('id_device')->nullable()->constrained('devices')->onDelete('set null');
            $table->string('Order_code')->nullable()->index();
            $table->string('KJ')->nullable();
            $table->string('Buyer')->nullable();
            $table->string('PO')->nullable();
            $table->string('Style')->nullable();
            $table->integer('Line')->nullable();
            $table->string('Subcon')->nullable();
            $table->string('Qty_order')->nullable();
            $table->decimal('Carton_weight_std', 8, 2)->nullable();
            $table->decimal('Pcs_weight_std', 8, 2)->nullable();
            $table->integer('PCS')->nullable();
            $table->integer('Ctn')->nullable();
            $table->integer('Less_Ctn')->nullable();
            $table->integer('Pcs_Less_Ctn')->nullable();
            $table->date('Gac_date')->nullable();
            $table->string('Destination')->nullable();
            $table->string('Inspector')->nullable();
            $table->string('OPT_QC_TIMBANGAN')->nullable();
            $table->string('SPV_QC')->nullable();
            $table->string('CHIEF_FINISH_GOOD')->nullable();
            $table->string('status')->nullable();
            $table->text('keterangan')->nullable();
            $table->integer('checking_ke')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ordersheets');
    }
};
