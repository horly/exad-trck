<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('devices')
            ->where('status', 'offline')
            ->update(['status' => 'inactive']);

        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE devices MODIFY status VARCHAR(20) NOT NULL DEFAULT 'inactive'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE devices MODIFY status VARCHAR(20) NOT NULL DEFAULT 'offline'");
        }
    }
};
