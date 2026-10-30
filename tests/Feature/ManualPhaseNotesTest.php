<?php

namespace Tests\Feature;

use App\Models\Manual;
use App\Models\ManualEvent;
use App\Models\ManualPhase;
use App\Models\ManualPhaseNote;
use App\Models\ManualPhaseNoteAudit;
use App\Models\ManualPhaseVisit;
use App\Models\ManualType;
use App\Models\User;
use App\Services\ManualPhaseNotes;
use App\Services\TransferredPhaseVisits;
use Database\Seeders\ManualPhasesSeeder;
use Database\Seeders\ManualTypesSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ManualPhaseNotesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('sqlite', DB::getDriverName());
        $this->seed([ManualPhasesSeeder::class, ManualTypesSeeder::class, RoleSeeder::class]);
    }

    private function setupManual(): array
    {
        $manual = Manual::create(['manual_name' => 'Nota de prueba', 'manual_types_id' => ManualType::first()->id,
            'manual_phases_id' => ManualPhase::where('slug', 'planning')->value('id'), 'research_advances' => 2,
            'experimentation_advances' => 2, 'is_active' => true, 'is_published' => false]);
        return [$manual, ManualPhase::where('slug', 'planning')->firstOrFail()];
    }

    private function actor(array $roles, bool $active = true): User
    {
        $actor = User::factory()->create(['is_active' => $active]);
        $actor->syncRoles($roles);
        $this->actingAs($actor);
        return $actor;
    }

    public function test_phase_responsible_can_create_and_clear_note_with_separate_audit(): void
    {
        [$manual, $phase] = $this->setupManual();
        $actor = $this->actor(['planificador']);
        $service = app(ManualPhaseNotes::class);

        $manual = $service->save($manual->id, $phase->id, $actor, 0, 'Observación contextual');
        $this->assertSame('Observación contextual', ManualPhaseNote::firstOrFail()->note);
        $this->assertSame(1, $manual->lock_version);

        $manual = $service->save($manual->id, $phase->id, $actor, 1, '   ');
        $this->assertNull(ManualPhaseNote::firstOrFail()->note);
        $this->assertSame(2, $manual->lock_version);
        $this->assertSame(2, ManualPhaseNoteAudit::count());
        $this->assertSame('Observación contextual', ManualPhaseNoteAudit::latest('id')->first()->before_note);
        $this->assertNull(ManualPhaseNoteAudit::latest('id')->first()->after_note);
        $this->assertSame($actor->id, ManualPhaseNoteAudit::latest('id')->first()->user_id);
    }

    public function test_phase_responsible_can_edit_their_transferred_phase_admin_can_edit_any_and_other_roles_cannot(): void
    {
        [$manual, $phase] = $this->setupManual();
        $service = app(ManualPhaseNotes::class);
        $manual->update(['manual_phases_id' => ManualPhase::where('slug', 'research')->value('id')]);
        $planificador = $this->actor(['planificador']);
        $manual = $service->save($manual->id, $phase->id, $planificador, 0, 'Fase transferida');
        $this->assertSame('Fase transferida', ManualPhaseNote::firstOrFail()->note);

        $outsider = $this->actor(['experimentador']);
        try {
            $service->save($manual->id, $phase->id, $outsider, 1, 'No');
            $this->fail('Se esperaba denegación para rol de otra fase.');
        } catch (AccessDeniedHttpException) {
            $this->assertDatabaseCount('manual_phase_notes', 1);
        }

        $admin = $this->actor(['admin']);
        $manual = $service->save($manual->id, $phase->id, $admin, 1, 'Admin puede editar fase transferida');
        $this->assertSame('Admin puede editar fase transferida', ManualPhaseNote::firstOrFail()->note);
    }

    public function test_stale_version_conflicts_and_invalid_length_write_nothing(): void
    {
        [$manual, $phase] = $this->setupManual();
        $actor = $this->actor(['planificador']);
        $service = app(ManualPhaseNotes::class);
        $manual = $service->save($manual->id, $phase->id, $actor, 0, 'Primera');

        try {
            $service->save($manual->id, $phase->id, $actor, 0, 'Conflicto');
            $this->fail('Se esperaba conflicto de versión.');
        } catch (ConflictHttpException) {
            $this->assertSame('Primera', ManualPhaseNote::firstOrFail()->note);
        }
        try {
            $service->save($manual->id, $phase->id, $actor, 1, str_repeat('x', 1001));
            $this->fail('Se esperaba error de validación.');
        } catch (ValidationException) {
            $this->assertSame(1, ManualPhaseNoteAudit::count());
            $this->assertSame(1, (int) $manual->fresh()->lock_version);
        }
    }

    public function test_notes_and_audits_are_not_in_general_manual_event_payloads(): void
    {
        [$manual, $phase] = $this->setupManual();
        $actor = $this->actor(['planificador']);
        app(ManualPhaseNotes::class)->save($manual->id, $phase->id, $actor, 0, 'NOTA_PRIVADA_PRUEBA');
        $this->assertDatabaseCount('manual_events', 0);
        $this->assertDatabaseHas('manual_phase_notes', ['note' => 'NOTA_PRIVADA_PRUEBA']);
        $this->assertDatabaseHas('manual_phase_note_audits', ['after_note' => 'NOTA_PRIVADA_PRUEBA']);
    }

    public function test_http_access_guest_none_inactive_functional_supervisor_admin_and_multirole(): void
    {
        [$manual, $phase] = $this->setupManual();
        $url = route('manuals.phase-notes.show', [$manual->id, $phase->id]);

        $this->get($url)->assertRedirect(route('login'));
        $none = $this->actor(['none']);
        $this->get($url)->assertForbidden();
        $admin = $this->actor(['admin']);
        $this->get($url)->assertOk()->assertSee('Observación de la fase');
        $this->patch(route('manuals.phase-notes.update', [$manual->id, $phase->id]), ['lock_version' => 0, 'note' => 'Admin HTTP'])->assertRedirect($url);
        $this->assertSame('Admin HTTP', ManualPhaseNote::firstOrFail()->note);

        $this->actor(['supervisor']);
        $this->get($url)->assertOk()->assertSee('Admin HTTP');
        $this->patch(route('manuals.phase-notes.update', [$manual->id, $phase->id]), ['lock_version' => 1, 'note' => 'Bloqueado'])->assertForbidden();

        $this->actor(['admin'], false);
        $this->get($url)->assertForbidden();
        $this->actor(['planificador', 'investigador']);
        $this->get($url)->assertOk();
        $this->patch(route('manuals.phase-notes.update', [$manual->id, $phase->id]), ['lock_version' => 1, 'note' => 'Multirole'])->assertRedirect($url);
        $this->get(route('manuals.phase-notes.show', [999999, $phase->id]))->assertNotFound();
        $this->get(route('manuals.phase-notes.show', [$manual->id, 999999]))->assertNotFound();
    }

    public function test_http_conflict_is_atomic_and_invalid_type_or_length_returns_422(): void
    {
        [$manual, $phase] = $this->setupManual();
        $this->actor(['planificador']);
        $url = route('manuals.phase-notes.update', [$manual->id, $phase->id]);
        $this->patch($url, ['lock_version' => 0, 'note' => 'Actual'])->assertRedirect();
        $this->patchJson($url, ['lock_version' => 0, 'note' => 'Obsoleta'])->assertStatus(409);
        $this->assertSame('Actual', ManualPhaseNote::firstOrFail()->note);
        $this->assertSame(1, ManualPhaseNoteAudit::count());

        $this->patchJson($url, ['lock_version' => 1, 'note' => ['no', 'string']])->assertStatus(422);
        $this->patchJson($url, ['lock_version' => 1, 'note' => str_repeat('x', 1001)])->assertStatus(422);
        $this->assertSame('Actual', ManualPhaseNote::firstOrFail()->note);
        $this->assertSame(1, ManualPhaseNoteAudit::count());
        $this->assertSame(1, (int) $manual->fresh()->lock_version);
    }

    public function test_note_sentinel_is_absent_from_dossier_dashboard_pdf_and_general_history_but_present_in_phase_context(): void
    {
        [$manual, $phase] = $this->setupManual();
        $actor = $this->actor(['planificador']);
        app(ManualPhaseNotes::class)->save($manual->id, $phase->id, $actor, 0, 'NOTA_SENTINEL_FASE_PRIVADA');
        $this->get(route('manuals.phase-notes.show', [$manual->id, $phase->id]))->assertOk()->assertSee('NOTA_SENTINEL_FASE_PRIVADA');

        $this->actor(['supervisor']);
        foreach ([
            route('manuals.show', $manual->id), route('manuals.history', $manual->id),
            route('dashboard'), route('reports.manuals'),
        ] as $url) {
            $response = $this->get($url)->assertOk();
            $this->assertStringNotContainsString('NOTA_SENTINEL_FASE_PRIVADA', $response->getContent(), $url);
        }
    }

    public function test_transferred_visits_are_filtered_paged_separately_and_keep_multiple_stays(): void
    {
        [$manual, $phase] = $this->setupManual();
        $nextPhase = ManualPhase::where('slug', 'research')->firstOrFail();
        $manual->update(['manual_phases_id' => $nextPhase->id]);
        $exit = ManualEvent::create(['manual_id' => $manual->id, 'action' => 'advance']);
        for ($i = 1; $i <= 16; $i++) {
            ManualPhaseVisit::create([
                'manual_id' => $manual->id, 'manual_phase_id' => $phase->id, 'entered_on' => '2026-01-01',
                'exited_on' => '2026-02-01', 'entered_recorded_at' => '2026-01-01 10:00:00',
                'exited_recorded_at' => '2026-02-01 10:00:00', 'entry_event_id' => null,
                'exit_event_id' => $exit->id, 'entry_basis' => 'phase_start', 'source' => 'reconstructed',
            ]);
        }
        ManualPhaseVisit::create([
            'manual_id' => $manual->id, 'manual_phase_id' => $nextPhase->id, 'entered_on' => '2026-02-01',
            'exited_on' => '2026-03-01', 'entered_recorded_at' => '2026-02-01 10:00:00',
            'exited_recorded_at' => '2026-03-01 10:00:00', 'exit_event_id' => $exit->id,
            'entry_basis' => 'phase_start', 'source' => 'reconstructed',
        ]);
        request()->query->replace(['phase' => 'planning', 'q' => 'Nota', 'map' => ManualType::first()->doctrinal_map_id,
            'type' => ManualType::first()->id, 'classification' => 'unknown', 'manuals_page' => 2, 'transferred_page' => 2]);
        $visits = app(TransferredPhaseVisits::class)->paginate($phase, request());
        $this->assertSame(16, $visits->total());
        $this->assertSame(2, $visits->currentPage());
        $this->assertSame(1, $visits->count());
        $this->assertStringContainsString('manuals_page=2', $visits->url(1));
        $html = view('partials.transferred-phase-visits', ['phase' => $phase, 'transferredVisits' => $visits])->render();
        $this->assertStringContainsString('Transferido', $html);
        $this->assertStringContainsString(route('manuals.phase-notes.show', [$manual->id, $phase->id]), $html);
        $this->assertStringContainsString('transferred_page=1', $html);
    }
}
