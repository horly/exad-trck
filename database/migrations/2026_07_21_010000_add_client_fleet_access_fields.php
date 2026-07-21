<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('fleet_id')->nullable()->after('subscription_id')->constrained()->nullOnDelete();
            $table->foreignId('created_by')->nullable()->after('fleet_id')->constrained('users')->nullOnDelete();
        });

        Schema::table('garages', function (Blueprint $table) {
            $table->foreignId('fleet_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->after('fleet_id')->constrained('users')->nullOnDelete();
        });

        DB::table('users')
            ->where('role', '!=', 'superadmin')
            ->whereNull('fleet_id')
            ->orderBy('id')
            ->get(['id', 'subscription_id'])
            ->each(function (object $user): void {
                $fleetId = DB::table('fleet_user')
                    ->where('user_id', $user->id)
                    ->orderByRaw("CASE WHEN permission = 'manager' THEN 0 ELSE 1 END")
                    ->orderBy('created_at')
                    ->value('fleet_id');

                if ($fleetId === null && $user->subscription_id !== null) {
                    $fleetId = DB::table('fleets')
                        ->where('subscription_id', $user->subscription_id)
                        ->oldest('id')
                        ->value('id');
                }

                if ($fleetId !== null) {
                    DB::table('users')->where('id', $user->id)->update(['fleet_id' => $fleetId]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('garages', function (Blueprint $table) {
            $table->dropConstrainedForeignId('created_by');
            $table->dropConstrainedForeignId('fleet_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('created_by');
            $table->dropConstrainedForeignId('fleet_id');
        });
    }
};
