<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('institutional_references', function (Blueprint $table) {
            $table->id();
            $table->foreignId('doctrinal_map_id')->unique()->constrained('doctrinal_maps')->restrictOnDelete();
            $table->unsignedInteger('expected_total')->nullable();
            $table->unsignedInteger('lock_version')->default(0);
            $table->timestamps();
        });
        Schema::create('institutional_reference_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('doctrinal_map_id')->constrained('doctrinal_maps')->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('before_total')->nullable();
            $table->unsignedInteger('after_total')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        throw new LogicException('La referencia institucional y su auditoría forman parte de la trazabilidad; la reversión automática está deshabilitada.');
    }
};
