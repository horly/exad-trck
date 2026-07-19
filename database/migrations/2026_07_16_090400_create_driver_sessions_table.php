<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('driver_sessions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('driver_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('driver_identifier_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('vehicle_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('device_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('start_position_id')->nullable()->constrained('positions')->nullOnDelete();
            $table->foreignId('end_position_id')->nullable()->constrained('positions')->nullOnDelete();
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->string('status', 20)->default('active');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['vehicle_id', 'started_at']);
            $table->index(['driver_id', 'started_at']);
            $table->index(['device_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('driver_sessions');
    }
};
