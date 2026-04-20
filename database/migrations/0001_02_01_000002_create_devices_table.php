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
        Schema::create('devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pending_firmware_id')->nullable()->constrained('firmwares')->onDelete('set null');
            $table->timestamp('ota_started_at')->nullable();
            $table->string('esp_id')->unique();
            $table->string('mac_esp')->unique();
            $table->string('name')->nullable();
            $table->string('device_type');
            $table->string('ip_address')->nullable();
            $table->string('current_firmware_version')->nullable();
            $table->string('wifi_ssid')->nullable();
            $table->string('wifi_password')->nullable();

            // User yang sedang memakainya
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');

            // Status yang jelas dan mudah dibaca
            $table->enum('status', ['offline', 'online', 'in_use'])->default('offline');
            $table->string('api_key')->nullable()->unique(); // token unik untuk tiap ESP
            $table->timestamp('api_key_generated_at')->nullable();

            // Waktu terakhir terlihat (heartbeat)
            $table->timestamp('last_seen_at')->nullable();

            // Optional: kapan terakhir online (bisa sama dengan last_seen_at)
            $table->timestamp('last_online_at')->nullable();
            $table->timestamp('wifi_updated_at')->nullable();
            $table->timestamps();

            // Index untuk performa tinggi
            $table->index(['status', 'user_id']);
            $table->index('esp_id');
            $table->index('last_seen_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->dropForeign(['pending_firmware_id']);
            $table->dropColumn('pending_firmware_id');
            $table->dropColumn('ota_started_at');
        });
    }
};
