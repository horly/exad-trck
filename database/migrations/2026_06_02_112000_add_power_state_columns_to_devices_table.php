<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('devices', function (Blueprint $table): void {
            $table->decimal('last_external_voltage', 8, 3)->nullable()->after('last_battery_level');
            $table->decimal('last_battery_voltage', 8, 3)->nullable()->after('last_external_voltage');
        });
    }

    public function down(): void
    {
        Schema::table('devices', function (Blueprint $table): void {
            $table->dropColumn([
                'last_external_voltage',
                'last_battery_voltage',
            ]);
        });
    }
};
