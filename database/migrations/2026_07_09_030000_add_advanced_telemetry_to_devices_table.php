<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('devices', function (Blueprint $table): void {
            $table->decimal('last_odometer_km', 12, 2)->nullable()->after('last_battery_voltage');
            $table->unsignedInteger('last_engine_seconds')->nullable()->after('last_odometer_km');
            $table->json('last_sensors')->nullable()->after('last_engine_seconds');
            $table->json('last_io')->nullable()->after('last_sensors');
            $table->json('last_raw_payload')->nullable()->after('last_io');
        });
    }

    public function down(): void
    {
        Schema::table('devices', function (Blueprint $table): void {
            $table->dropColumn([
                'last_odometer_km',
                'last_engine_seconds',
                'last_sensors',
                'last_io',
                'last_raw_payload',
            ]);
        });
    }
};
