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
        Schema::create('halls', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('owner_id')->constrained('experts');
            $table->string('owner_name');
            $table->string('lat');
            $table->string('lng');
            $table->string('address');
            $table->string('postal_code');
            $table->string('telephone');
            $table->foreignId('province_id')->constrained('provinces');
            $table->foreignId('city_id')->constrained('cities');
            $table->boolean('is_active')->default(true);
            $table->string('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('halls');
    }
};
