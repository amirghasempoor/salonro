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
        Schema::create('expert_service', function (Blueprint $table) {
            $table->id();
            $table->morphs('serviceable');
            $table->foreignId('service_id')->constrained('services');
            $table->unique(['serviceable_type', 'serviceable_id', 'service_id']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expert_service');
    }
};
