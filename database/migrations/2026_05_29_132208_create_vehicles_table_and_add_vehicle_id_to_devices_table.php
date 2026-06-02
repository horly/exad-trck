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
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fleet_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->string('registration_number', 40);
            $table->string('brand', 80)->nullable();
            $table->string('model', 80)->nullable();
            $table->string('color', 50)->nullable();
            $table->unsignedSmallInteger('year')->nullable();
            $table->string('vehicle_type', 40)->default('car')->index();
            $table->string('subscription_plan', 40)->default('basic')->index();
            $table->string('status', 40)->default('active')->index();
            $table->timestamps();

            $table->unique(['fleet_id', 'registration_number']);
            $table->index(['fleet_id', 'status']);
            $table->index(['fleet_id', 'subscription_plan']);
        });

        Schema::table('devices', function (Blueprint $table) {
            $table->foreignId('vehicle_id')
                ->nullable()
                ->after('fleet_id')
                ->constrained()
                ->nullOnDelete();

            $table->index(['vehicle_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->dropIndex(['vehicle_id', 'status']);
            $table->dropConstrainedForeignId('vehicle_id');
        });

        Schema::dropIfExists('vehicles');
    }
};
