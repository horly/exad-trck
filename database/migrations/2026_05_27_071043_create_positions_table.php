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
        Schema::create('positions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained()->restrictOnDelete();
            $table->string('imei', 20);
            $table->timestamp('gps_time')->nullable();
            $table->timestamp('server_time')->useCurrent();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->boolean('is_valid')->default(true);
            $table->unsignedSmallInteger('speed')->default(0);
            $table->unsignedSmallInteger('angle')->default(0);
            $table->integer('altitude')->nullable();
            $table->unsignedTinyInteger('satellites')->nullable();
            $table->boolean('ignition')->nullable();
            $table->boolean('movement')->nullable();
            $table->decimal('external_voltage', 8, 3)->nullable();
            $table->decimal('battery_voltage', 8, 3)->nullable();
            $table->unsignedBigInteger('odometer')->nullable();
            $table->json('raw_data')->nullable();
            $table->timestamps();

            $table->index('imei');
            $table->index('gps_time');
            $table->index('server_time');
            $table->index(['device_id', 'gps_time']);
            $table->index(['device_id', 'server_time']);
            $table->index(['latitude', 'longitude']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('positions');
    }
};
