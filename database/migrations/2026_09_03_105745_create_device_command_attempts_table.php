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
        Schema::create('device_command_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_command_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('attempt_number');
            $table->string('status', 32)->index();
            $table->string('frame_hash', 64)->nullable();
            $table->text('response_text')->nullable();
            $table->string('failure_code', 80)->nullable();
            $table->text('failure_message')->nullable();
            $table->timestamp('started_at');
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->unique(['device_command_id', 'attempt_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('device_command_attempts');
    }
};
