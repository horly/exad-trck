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
        Schema::create('device_immobilization_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->unique()->constrained()->cascadeOnDelete();
            $table->boolean('immobilize_dout1')->default(true);
            $table->boolean('immobilize_dout2')->default(true);
            $table->boolean('release_dout1')->default(false);
            $table->boolean('release_dout2')->default(false);
            $table->boolean('is_active')->default(true)->index();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->string('verification_note', 500)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('device_immobilization_profiles');
    }
};
