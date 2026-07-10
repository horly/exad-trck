<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alert_rules', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('fleet_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('vehicle_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('device_id')->nullable()->constrained('devices')->nullOnDelete();
            $table->string('name');
            $table->string('type');
            $table->string('category')->default('equipment');
            $table->string('severity')->default('medium');
            $table->string('scope_type')->default('all');
            $table->decimal('threshold_value', 10, 2)->nullable();
            $table->string('threshold_unit', 30)->nullable();
            $table->json('channels')->nullable();
            $table->json('schedule_days')->nullable();
            $table->time('starts_at')->nullable();
            $table->time('ends_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['category', 'type']);
            $table->index(['scope_type', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alert_rules');
    }
};
