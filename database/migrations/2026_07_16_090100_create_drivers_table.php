<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('drivers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('fleet_id')->constrained()->cascadeOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('employee_id', 80)->nullable();
            $table->string('phone', 40)->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->string('license_number', 100)->nullable();
            $table->string('license_type', 80)->nullable();
            $table->date('license_issued_at')->nullable();
            $table->date('license_expires_at')->nullable();
            $table->json('tags')->nullable();
            $table->string('photo_path')->nullable();
            $table->string('status', 20)->default('active');
            $table->timestamps();

            $table->unique(['fleet_id', 'employee_id']);
            $table->index(['fleet_id', 'status']);
            $table->index(['department_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('drivers');
    }
};
