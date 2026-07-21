<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('application_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('app_name')->default('EXAD Tracking');
            $table->string('short_name')->default('EXAD Tracking');
            $table->string('website_url')->nullable();
            $table->string('logo_path')->nullable();
            $table->string('favicon_path')->nullable();
            $table->string('primary_color', 7)->default('#171064');
            $table->string('secondary_color', 7)->default('#2F67E8');
            $table->string('button_color', 7)->default('#171064');
            $table->string('avatar_color', 7)->default('#1D4ED8');
            $table->string('accent_color', 7)->default('#6D3DF2');
            $table->string('sidebar_start_color', 7)->default('#1B146F');
            $table->string('sidebar_end_color', 7)->default('#0F0A43');
            $table->string('support_email')->nullable();
            $table->string('support_phone', 40)->nullable();
            $table->timestamps();
        });

        DB::table('application_settings')->insert([
            'id' => 1,
            'app_name' => 'EXAD Tracking',
            'short_name' => 'EXAD Tracking',
            'primary_color' => '#171064',
            'secondary_color' => '#2F67E8',
            'button_color' => '#171064',
            'avatar_color' => '#1D4ED8',
            'accent_color' => '#6D3DF2',
            'sidebar_start_color' => '#1B146F',
            'sidebar_end_color' => '#0F0A43',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('application_settings');
    }
};
