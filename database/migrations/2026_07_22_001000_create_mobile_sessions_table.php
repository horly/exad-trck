<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mobile_sessions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('access_token_id')->nullable()->unique()
                ->constrained('personal_access_tokens')->nullOnDelete();
            $table->foreignId('refresh_token_id')->nullable()->unique()
                ->constrained('personal_access_tokens')->nullOnDelete();
            $table->string('device_identifier', 128);
            $table->string('device_name', 100);
            $table->string('platform', 16);
            $table->string('app_version', 32)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->dateTime('access_expires_at');
            $table->dateTime('refresh_expires_at');
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'device_identifier']);
            $table->index(['user_id', 'revoked_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mobile_sessions');
    }
};
