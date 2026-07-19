<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alert_rule_states', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('alert_rule_id')->constrained()->cascadeOnDelete();
            $table->foreignId('device_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_triggered')->default(false);
            $table->decimal('last_value', 10, 2)->nullable();
            $table->timestamp('triggered_at')->nullable();
            $table->timestamps();

            $table->unique(['alert_rule_id', 'device_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alert_rule_states');
    }
};
