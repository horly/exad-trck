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
        Schema::table('devices', function (Blueprint $table) {
            $table->foreignId('fleet_id')
                ->nullable()
                ->after('subscription_id')
                ->constrained()
                ->nullOnDelete();

            $table->index(['subscription_id', 'fleet_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->dropIndex(['subscription_id', 'fleet_id']);
            $table->dropConstrainedForeignId('fleet_id');
        });
    }
};
