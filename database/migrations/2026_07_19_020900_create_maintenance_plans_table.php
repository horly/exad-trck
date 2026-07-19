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
        Schema::create('maintenance_plans', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $table->foreignId('garage_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('maintenance_type', 20)->default('preventive');
            $table->decimal('estimated_cost', 12, 2)->nullable();
            $table->boolean('is_recurring')->default(false);
            $table->date('next_due_date')->nullable();
            $table->unsignedSmallInteger('reminder_days')->default(0);
            $table->unsignedInteger('interval_days')->nullable();
            $table->decimal('next_due_odometer_km', 12, 2)->nullable();
            $table->unsignedInteger('reminder_odometer_km')->default(0);
            $table->unsignedInteger('interval_odometer_km')->nullable();
            $table->decimal('next_due_engine_hours', 12, 2)->nullable();
            $table->unsignedInteger('reminder_engine_hours')->default(0);
            $table->unsignedInteger('interval_engine_hours')->nullable();
            $table->string('status', 20)->default('active');
            $table->string('due_status', 20)->default('scheduled');
            $table->timestamp('due_alert_sent_at')->nullable();
            $table->timestamp('overdue_alert_sent_at')->nullable();
            $table->timestamp('last_completed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'due_status']);
            $table->index(['vehicle_id', 'status']);
            $table->index('next_due_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maintenance_plans');
    }
};
