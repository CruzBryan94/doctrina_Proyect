<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('manual_phase_visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('manual_id')->constrained()->restrictOnDelete();
            $table->foreignId('manual_phase_id')->constrained('manual_phases')->restrictOnDelete();
            $table->date('entered_on')->nullable();
            $table->date('exited_on')->nullable();
            $table->dateTime('entered_recorded_at');
            $table->dateTime('exited_recorded_at')->nullable();
            $table->foreignId('entry_event_id')->nullable()->unique()->constrained('manual_events')->restrictOnDelete();
            $table->foreignId('exit_event_id')->nullable()->constrained('manual_events')->restrictOnDelete();
            $table->string('entry_basis', 20);
            $table->string('source', 20);
            $table->timestamps();
            $table->index(['manual_id', 'exit_event_id']);
            $table->index(['manual_phase_id', 'entered_on', 'exited_on'], 'phase_visit_period');
        });
    }

    public function down(): void
    {
        throw new RuntimeException('Las permanencias contienen evidencia histórica. Restaurar un respaldo revisado para revertir sin eliminar evidencia.');
    }
};
