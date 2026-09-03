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
        Schema::create('device_commands', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('device_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('vehicle_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('fleet_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('imei', 20)->index();
            $table->string('vehicle_label')->nullable();
            $table->string('action', 24)->index();
            $table->string('status', 32)->default('pending_safety')->index();
            $table->string('command_text', 120);
            $table->json('desired_outputs');
            $table->text('reason');
            $table->string('request_ip', 45)->nullable();
            $table->string('request_user_agent', 500)->nullable();
            $table->json('safety_snapshot')->nullable();
            $table->timestamp('safety_checked_at')->nullable();
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->uuid('claim_token')->nullable()->unique();
            $table->timestamp('claimed_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('expires_at')->index();
            $table->timestamp('failed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('failure_code', 80)->nullable();
            $table->text('failure_message')->nullable();
            $table->text('response_text')->nullable();
            $table->timestamps();

            $table->index(['device_id', 'status', 'expires_at']);
            $table->index(['status', 'claimed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('device_commands');
    }
};
