<details class="panel transferred-phase-panel" id="transferred-phase-visits">
    <summary class="panel-heading"><strong>Salidas y transferencias de {{ $phase->phase_name }}</strong><span class="badge-soft ms-2">{{ $transferredVisits->total() }}</span></summary>
    <div class="panel-body">
        <p class="form-text">Cada permanencia aparece por separado. Las fechas efectivas se muestran junto a la fecha de registro.</p>
        <div class="table-responsive"><table class="table table-sm align-middle">
            <thead><tr><th>Manual</th><th>Salida</th><th>Fechas registradas</th><th>Contexto</th></tr></thead>
            <tbody>
            @forelse($transferredVisits as $visit)
                @php($exitLabel = $visit->exitEvent?->action === 'retreat' ? 'Retroceso' : ($visit->exitEvent?->action === 'advance' ? 'Transferido' : 'Salida registrada'))
                <tr>
                    <td><a href="{{ route('manuals.show', $visit->manual_id) }}">{{ $visit->manual->manual_name }}</a><div class="small text-muted">ART-{{ str_pad($visit->manual_id, 4, '0', STR_PAD_LEFT) }} · {{ $visit->manual->manualType?->code ?? 'Sin tipo' }}</div></td>
                    <td>{{ $exitLabel }}<div class="small">{{ $visit->entered_on?->format('d/m/Y') ?? 'Entrada efectiva desconocida' }} – {{ $visit->exited_on?->format('d/m/Y') ?? 'Salida efectiva desconocida' }}</div></td>
                    <td>Entrada: {{ $visit->entered_recorded_at?->timezone(config('app.timezone'))->format('d/m/Y H:i') ?? 'Sin registro' }}<br>Salida: {{ $visit->exited_recorded_at?->timezone(config('app.timezone'))->format('d/m/Y H:i') ?? 'Sin registro' }}</td>
                    <td><a class="btn btn-sm btn-light" href="{{ route('manuals.phase-notes.show', [$visit->manual_id, $phase->id]) }}">Nota e historial de fase</a></td>
                </tr>
            @empty
                <tr><td colspan="4" class="text-muted">No hay permanencias cerradas para esta fase con estos filtros.</td></tr>
            @endforelse
            </tbody>
        </table></div>
        <div>{{ $transferredVisits->links() }}</div>
    </div>
</details>
