<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('devices', function (Blueprint $table): void {
            $table->string('last_driver_identifier_uid', 100)->nullable()->after('last_io');
        });
    }

    public function down(): void
    {
        Schema::table('devices', function (Blueprint $table): void {
            $table->dropColumn('last_driver_identifier_uid');
        });
    }
};
