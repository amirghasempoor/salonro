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
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('user_name');
            $table->foreignId('expert_id')->constrained('experts')->cascadeOnDelete();
            $table->string('expert_name');
            $table->foreignId('hall_id')->constrained('halls')->cascadeOnDelete();
            $table->string('hall_name');
            $table->foreignId('state_id')->constrained('reservation_states')->cascadeOnDelete();
            $table->string('state_name');
            $table->dateTime('from_date');
            $table->dateTime('to_date');
            $table->string('services');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};
