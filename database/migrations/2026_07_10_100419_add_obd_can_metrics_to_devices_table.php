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
        Schema::table('devices', function (Blueprint $table) {
            $table->unsignedInteger('last_obd_rpm')->nullable()->after('last_engine_seconds');
            $table->unsignedSmallInteger('last_obd_speed')->nullable()->after('last_obd_rpm');
            $table->decimal('last_obd_throttle_percent', 5, 2)->nullable()->after('last_obd_speed');
            $table->decimal('last_obd_engine_temperature_c', 6, 2)->nullable()->after('last_obd_throttle_percent');
            $table->decimal('last_obd_module_voltage', 8, 3)->nullable()->after('last_obd_engine_temperature_c');
            $table->decimal('last_obd_engine_load_percent', 5, 2)->nullable()->after('last_obd_module_voltage');
            $table->unsignedInteger('last_obd_fault_distance_km')->nullable()->after('last_obd_engine_load_percent');
            $table->unsignedSmallInteger('last_obd_errors_count')->nullable()->after('last_obd_fault_distance_km');
            $table->unsignedInteger('last_obd_distance_since_clear_km')->nullable()->after('last_obd_errors_count');
            $table->decimal('last_can_fuel_level_percent', 5, 2)->nullable()->after('last_obd_distance_since_clear_km');
            $table->decimal('last_can_total_mileage_km', 12, 2)->nullable()->after('last_can_fuel_level_percent');
            $table->timestamp('last_obd_updated_at')->nullable()->after('last_can_total_mileage_km');
            $table->timestamp('last_diagnostic_updated_at')->nullable()->after('last_obd_updated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->dropColumn([
                'last_obd_rpm',
                'last_obd_speed',
                'last_obd_throttle_percent',
                'last_obd_engine_temperature_c',
                'last_obd_module_voltage',
                'last_obd_engine_load_percent',
                'last_obd_fault_distance_km',
                'last_obd_errors_count',
                'last_obd_distance_since_clear_km',
                'last_can_fuel_level_percent',
                'last_can_total_mileage_km',
                'last_obd_updated_at',
                'last_diagnostic_updated_at',
            ]);
        });
    }
};
