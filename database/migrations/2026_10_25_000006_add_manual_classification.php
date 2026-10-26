<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('manuals', function (Blueprint $table) {
            // Existing unknown classifications stay unknown until explicitly supplied.
            $table->string('classification', 20)->nullable()->index();
        });
    }

    public function down(): void
    {
        throw new LogicException('La clasificación contiene datos históricos. Restaurar respaldo para revertir.');
    }
};
