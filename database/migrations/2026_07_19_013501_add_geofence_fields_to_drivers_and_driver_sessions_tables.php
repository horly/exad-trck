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
        Schema::table('drivers', function (Blueprint $table): void {
            $table->decimal('location_latitude', 10, 7)->nullable()->after('address');
            $table->decimal('location_longitude', 10, 7)->nullable()->after('location_latitude');
        });

        Schema::table('driver_sessions', function (Blueprint $table): void {
            $table->string('geofence_status', 20)->nullable()->after('status');
            $table->unsignedInteger('geofence_distance_meters')->nullable()->after('geofence_status');
            $table->timestamp('geofence_updated_at')->nullable()->after('geofence_distance_meters');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('driver_sessions', function (Blueprint $table): void {
            $table->dropColumn([
                'geofence_status',
                'geofence_distance_meters',
                'geofence_updated_at',
            ]);
        });

        Schema::table('drivers', function (Blueprint $table): void {
            $table->dropColumn(['location_latitude', 'location_longitude']);
        });
    }
};
