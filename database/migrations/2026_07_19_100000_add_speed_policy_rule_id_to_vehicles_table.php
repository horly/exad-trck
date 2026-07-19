<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicles', function (Blueprint $table): void {
            $table->foreignId('speed_policy_rule_id')
                ->nullable()
                ->after('subscription_plan')
                ->constrained('alert_rules')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('speed_policy_rule_id');
        });
    }
};
