<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('departments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('fleet_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code', 50)->nullable();
            $table->text('description')->nullable();
            $table->string('status', 20)->default('active');
            $table->timestamps();

            $table->unique(['fleet_id', 'name']);
            $table->unique(['fleet_id', 'code']);
            $table->index(['fleet_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('departments');
    }
};
