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
        Schema::create('manuals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('manual_types_id');
            $table->string('manual_name', 50);
            $table->unsignedBigInteger('manual_phases_id');
            $table->string('code', 20)->nullable();
            $table->string('observations', 255)->nullable();
            $table->date('publication_year')->nullable();
            $table->boolean('is_published')->default(false);
            $table->timestamps();


            // Foreign keys
            $table->foreign('manual_types_id')->references('id')->on('manual_types')->onDelete('cascade');
            $table->foreign('manual_phases_id')->references('id')->on('manual_phases')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('manuals');
    }
};
