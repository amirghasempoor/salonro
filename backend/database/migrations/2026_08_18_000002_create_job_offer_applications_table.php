<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_offer_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_offer_id')->constrained('job_offers')->cascadeOnDelete();
            $table->foreignId('expert_id')->constrained('experts');
            $table->unsignedTinyInteger('status')->default(0);
            $table->timestamps();

            $table->unique(['job_offer_id', 'expert_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_offer_applications');
    }
};
