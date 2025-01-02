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
        Schema::create('manual_committee_members', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('committee_members_id');
            $table->unsignedBigInteger('manuals_id');
            $table->unsignedBigInteger('committee_type_id');
            $table->timestamps();

            // Foreign keys
            $table->foreign('committee_members_id')->references('id')->on('committee_members')->onDelete('cascade');
            $table->foreign('manuals_id')->references('id')->on('manuals')->onDelete('cascade');
            $table->foreign('committee_type_id')->references('id')->on('committee_type')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('manual_committee_members');
    }
};
