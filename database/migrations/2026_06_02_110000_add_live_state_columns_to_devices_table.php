<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('devices', function (Blueprint $table): void {
            $table->boolean('last_ignition')->nullable()->after('last_angle');
            $table->boolean('last_movement')->nullable()->after('last_ignition');
            $table->unsignedTinyInteger('last_satellites')->nullable()->after('last_movement');
            $table->unsignedTinyInteger('last_gsm_signal')->nullable()->after('last_satellites');
            $table->unsignedTinyInteger('last_battery_level')->nullable()->after('last_gsm_signal');
            $table->string('last_address')->nullable()->after('last_battery_level');
        });
    }

    public function down(): void
    {
        Schema::table('devices', function (Blueprint $table): void {
            $table->dropColumn([
                'last_ignition',
                'last_movement',
                'last_satellites',
                'last_gsm_signal',
                'last_battery_level',
                'last_address',
            ]);
        });
    }
};
