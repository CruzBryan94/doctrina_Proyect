<?php

namespace App\Console\Commands;

use App\Models\Manual;
use App\Services\ManualPhaseHistory;
use Illuminate\Console\Command;

class ReconstructManualPhaseHistory extends Command
{
    protected $signature = 'manuals:reconstruct-history {--manual= : ID de un manual para acotar la revisión} {--apply : Persistir evidencia después de revisar respaldo y prueba en copia}';

    protected $description = 'Revisar por defecto, o reconstruir explícitamente, solo permanencias acreditadas en eventos; sin inventar fechas.';

    public function handle(ManualPhaseHistory $history): int
    {
        if ($this->option('manual') !== null && (! ctype_digit((string) $this->option('manual')) || (int) $this->option('manual') < 1)) {
            $this->error('El ID del manual debe ser un entero positivo.');

            return self::INVALID;
        }
        $this->info($this->option('apply') ? 'Aplicación explícita: no se modificarán visitas ya registradas ni eventos originales.' : 'Solo revisión: no se guardan cambios. Revisar respaldo y copia antes de --apply.');
        Manual::when($this->option('manual'), fn ($q, $id) => $q->whereKey($id))->orderBy('id')->chunkById(100, function ($manuals) use ($history) {
            foreach ($manuals as $manual) {
                $result = $history->reconstruct($manual, (bool) $this->option('apply'));
                $this->line('Manual '.$result['manual_id'].': '.$result['status'].'; '.$result['events'].' eventos candidatos.');
            }
        });
        $this->warn('La ausencia de una fecha o evento sigue siendo desconocida; esta revisión no acredita cobertura completa.');

        return self::SUCCESS;
    }
}
