<?php

use App\Enums\UserRole;
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
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('subscription_id')
                ->nullable()
                ->after('id')
                ->constrained()
                ->nullOnDelete();
            $table->string('role', 24)->default(UserRole::User->value)->after('email_verified_at')->index();
            $table->string('status', 24)->default('active')->after('role')->index();
            $table->timestamp('disabled_at')->nullable()->after('status');
            $table->json('permissions')->nullable()->after('disabled_at');

            $table->index(['subscription_id', 'role']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['subscription_id', 'role']);
            $table->dropConstrainedForeignId('subscription_id');
            $table->dropColumn(['role', 'status', 'disabled_at', 'permissions']);
        });
    }
};
