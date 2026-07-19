<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('driver_identifiers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('driver_id')->constrained()->cascadeOnDelete();
            $table->string('type', 30)->default('rfid');
            $table->string('uid', 100)->unique();
            $table->boolean('active')->default(true);
            $table->timestamp('issued_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['driver_id', 'active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('driver_identifiers');
    }
};
