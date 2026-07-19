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
        Schema::create('maintenance_records', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('maintenance_plan_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $table->foreignId('garage_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->string('maintenance_type', 20);
            $table->text('description')->nullable();
            $table->date('scheduled_due_date')->nullable();
            $table->decimal('scheduled_due_odometer_km', 12, 2)->nullable();
            $table->decimal('scheduled_due_engine_hours', 12, 2)->nullable();
            $table->dateTime('performed_at');
            $table->decimal('odometer_km', 12, 2)->nullable();
            $table->decimal('engine_hours', 12, 2)->nullable();
            $table->decimal('estimated_cost', 12, 2)->nullable();
            $table->decimal('actual_cost', 12, 2)->nullable();
            $table->text('notes')->nullable();
            $table->string('status', 20)->default('completed');
            $table->timestamps();

            $table->index(['vehicle_id', 'performed_at']);
            $table->index(['garage_id', 'performed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maintenance_records');
    }
};
