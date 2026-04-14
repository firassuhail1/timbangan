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
        Schema::create('firmwares', function (Blueprint $table) {
            $table->id();

            $table->string('version');
            $table->string('device_type');

            $table->string('file_name');
            $table->string('file_path');

            $table->string('checksum', 64)->nullable(); // sha256 = 64 karakter
            $table->string('download_url')->nullable();

            $table->text('notes')->nullable();

            $table->enum('status', ['draft', 'uploaded', 'published', 'expired'])
                ->default('uploaded');

            $table->timestamp('released_at')->nullable();

            $table->timestamps();

            $table->unique(['version', 'device_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('firmwares');
    }
};
