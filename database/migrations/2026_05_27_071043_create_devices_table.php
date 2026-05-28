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
            $table->string('imei', 20)->unique();
            $table->string('name')->nullable();
            $table->string('model')->nullable();
            $table->string('sim_number', 30)->nullable();
            $table->string('operator_name', 50)->nullable();
            $table->string('protocol', 10)->default('TCP');
            $table->string('codec', 20)->nullable();
            $table->string('status', 20)->default('offline');
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamp('last_position_at')->nullable();
            $table->decimal('last_latitude', 10, 7)->nullable();
            $table->decimal('last_longitude', 10, 7)->nullable();
            $table->unsignedSmallInteger('last_speed')->default(0);
            $table->unsignedSmallInteger('last_angle')->default(0);
            $table->json('settings')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('last_seen_at');
            $table->index(['last_latitude', 'last_longitude']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('devices');
    }
};
