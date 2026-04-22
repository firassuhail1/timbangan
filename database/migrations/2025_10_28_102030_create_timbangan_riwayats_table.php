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
        Schema::create('timbangan_riwayats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_user')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('id_device')->nullable()->constrained('devices')->onDelete('set null');
            $table->foreignId('id_ordersheet')
                ->constrained('ordersheets')
                ->onDelete('cascade');
            $table->decimal('berat', 10, 2)->nullable();
            $table->string('no_box')->nullable();
            $table->integer('pcs')->nullable()->default(0);
            $table->decimal('rasio_batas_beban_min', 8, 2)->nullable();
            $table->decimal('rasio_batas_beban_max', 8, 2)->nullable();
            $table->timestamp('waktu_timbang')->useCurrent();
            $table->enum('status', ['Pending', 'Success', 'Rejected'])->default('Pending');
            $table->timestamps();

            $table->index(['id_ordersheet', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('timbangan_riwayats');
    }
};
