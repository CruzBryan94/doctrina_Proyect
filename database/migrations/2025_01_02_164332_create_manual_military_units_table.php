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
        Schema::create('manual_military_units', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('manuals_id');
            $table->unsignedBigInteger('committee_type_id');
            $table->unsignedBigInteger('military_units_id');
            $table->timestamps();

            // Foreign keys
            $table->foreign('manuals_id')->references('id')->on('manuals')->onDelete('cascade');
            $table->foreign('committee_type_id')->references('id')->on('committee_type')->onDelete('cascade');
            $table->foreign('military_units_id')->references('id')->on('military_units')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('manual_military_units');
    }
};
