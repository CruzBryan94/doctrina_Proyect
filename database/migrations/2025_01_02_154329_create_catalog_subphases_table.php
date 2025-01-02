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
        Schema::create('catalog_subphases', function (Blueprint $table) {
            $table->id();
            $table->string('suphase_name', 150);
            $table->unsignedBigInteger('manual_phases_id');
            $table->timestamps();

            // Foreign key
            $table->foreign('manual_phases_id')->references('id')->on('manual_phases')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('catalog_subphases');
    }
};
