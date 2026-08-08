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
        Schema::create('hall_service', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hall_id')->constrained('halls');
            $table->foreignId('service_id')->constrained('services');
            $table->string('description')->nullable();
            $table->float('duration');
            $table->float('price');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hall_service');
    }
};
