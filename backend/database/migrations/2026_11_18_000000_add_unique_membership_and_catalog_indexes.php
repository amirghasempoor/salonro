<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One membership per expert and hall, and one catalog row per hall and service.
     * Fails if duplicate rows already exist; remove them before migrating.
     */
    public function up(): void
    {
        Schema::table('expert_hall', function (Blueprint $table) {
            $table->unique(['expert_id', 'hall_id']);
        });

        Schema::table('hall_service', function (Blueprint $table) {
            $table->unique(['hall_id', 'service_id']);
        });
    }

    public function down(): void
    {
        Schema::table('expert_hall', function (Blueprint $table) {
            $table->dropUnique(['expert_id', 'hall_id']);
        });

        Schema::table('hall_service', function (Blueprint $table) {
            $table->dropUnique(['hall_id', 'service_id']);
        });
    }
};
