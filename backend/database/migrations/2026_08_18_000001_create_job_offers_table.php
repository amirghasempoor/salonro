<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hall_id')->constrained('halls');
            $table->string('hall_name');
            $table->foreignId('expert_id')->constrained('experts');
            $table->foreignId('profession_id')->constrained('professions')->cascadeOnDelete();
            $table->string('profession_name');
            $table->foreignId('province_id')->constrained('provinces')->cascadeOnDelete();
            $table->string('province_name');
            $table->foreignId('city_id')->constrained('cities')->cascadeOnDelete();
            $table->string('city_name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_offers');
    }
};
