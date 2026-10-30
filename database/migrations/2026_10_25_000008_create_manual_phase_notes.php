<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('manual_phase_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('manual_id')->constrained()->restrictOnDelete();
            $table->foreignId('manual_phase_id')->constrained('manual_phases')->restrictOnDelete();
            $table->text('note')->nullable();
            $table->timestamps();
            $table->unique(['manual_id', 'manual_phase_id'], 'manual_phase_note_unique');
        });

        Schema::create('manual_phase_note_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('manual_id')->constrained()->restrictOnDelete();
            $table->foreignId('manual_phase_id')->constrained('manual_phases')->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->text('before_note')->nullable();
            $table->text('after_note')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['manual_id', 'manual_phase_id', 'created_at'], 'manual_phase_note_audit_history');
        });
    }

    public function down(): void
    {
        throw new LogicException('Las notas y su auditoría son evidencia. Restaurar un respaldo revisado para revertir.');
    }
};
