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
        Schema::create('maintenance_documents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('maintenance_plan_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('maintenance_record_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('disk', 40)->default('public');
            $table->string('path');
            $table->string('original_name');
            $table->string('mime_type', 120)->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->timestamps();

            $table->index(['maintenance_plan_id', 'maintenance_record_id'], 'maintenance_documents_owner_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maintenance_documents');
    }
};
