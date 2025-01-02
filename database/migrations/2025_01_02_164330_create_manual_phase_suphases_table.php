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
        Schema::create('manual_phase_suphases', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('manuals_id');
            $table->unsignedBigInteger('catalog_subphases_id');
            $table->unsignedBigInteger('manual_phases_id');
            $table->boolean('is_completed')->default(false);
            $table->date('completation_date')->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('manuals_id')->references('id')->on('manuals')->onDelete('cascade');
            $table->foreign('catalog_subphases_id')->references('id')->on('catalog_subphases')->onDelete('cascade');
            $table->foreign('manual_phases_id')->references('id')->on('manual_phases')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('manual_phase_suphases');
    }
};
