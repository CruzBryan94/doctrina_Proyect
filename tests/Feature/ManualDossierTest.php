<?php

namespace Tests\Feature;

use App\Models\CommitteeMember;
use App\Models\CatalogSubphase;
use App\Models\Grade;
use App\Models\Manual;
use App\Models\ManualEvent;
use App\Models\ManualPhase;
use App\Models\ManualPhaseSuphase;
use App\Models\ManualPhaseVisit;
use App\Models\ManualType;
use App\Models\User;
use App\Services\ManualDossier;
use App\Services\ManualWorkflow;
use Database\Seeders\CatalogSubphasesSeeder;
use Database\Seeders\CommitteeTypeSeeder;
use Database\Seeders\ManualPhasesSeeder;
use Database\Seeders\ManualTypesSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ManualDossierTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('sqlite', DB::getDriverName());
        $this->seed([ManualPhasesSeeder::class, ManualTypesSeeder::class, CatalogSubphasesSeeder::class, CommitteeTypeSeeder::class, RoleSeeder::class]);
    }

    private function actor(array $roles, bool $active = true): User
    {
        $user = User::factory()->create(['is_active' => $active]);
        $user->syncRoles($roles);
        $this->actingAs($user);

        return $user;
    }

    private function manual(string $phase = 'experimentation', bool $published = false): Manual
    {
        $manual = Manual::create([
            'manual_name' => 'Manual de ficha individual', 'manual_types_id' => ManualType::first()->id,
            'manual_phases_id' => ManualPhase::where('slug', $phase)->value('id'),
            'research_advances' => 2, 'experimentation_advances' => 2,
            'is_active' => true, 'is_published' => $published,
        ]);
        if (! $published) {
            app(ManualWorkflow::class)->configure($manual, true);
        }

        return $manual;
    }

    public function test_functional_roles_and_supervisor_can_consult_without_cross_phase_edits(): void
    {
        $manual = $this->manual();
        foreach (['planificador', 'investigador', 'edición', 'cgft', 'publicados', 'supervisor'] as $role) {
            $this->actor([$role]);
            $this->get(route('manuals.show', $manual))->assertOk()->assertSee('Manual de ficha individual')
                ->assertSee('Antecedentes no acreditados')->assertDontSee('Editar datos del manual')->assertDontSee('Registrar seguimiento');
        }
        $this->actor(['experimentador']);
        $this->get(route('manuals.show', $manual))->assertOk()->assertSee('Editar datos del manual')->assertSee('Registrar seguimiento');
        $this->actor(['admin']);
        $this->get(route('manuals.show', $manual))->assertOk()->assertSee('Editar datos del manual');
    }

    public function test_none_inactive_guest_and_direct_unknown_id_are_rejected(): void
    {
        $manual = $this->manual();
        $this->get(route('manuals.show', $manual))->assertRedirect(route('login'));
        $this->actor(['none']);
        $this->get(route('manuals.show', $manual))->assertForbidden();
        $this->actor(['admin'], false);
        $this->get(route('manuals.show', $manual))->assertForbidden();
        $this->actor(['supervisor']);
        $this->get(route('manuals.show', 999999))->assertNotFound();
    }

    public function test_advanced_entry_does_not_present_unknown_prior_phases_as_failed(): void
    {
        $manual = $this->manual();
        $this->actor(['supervisor']);
        $response = $this->get(route('manuals.show', $manual))->assertOk()
            ->assertSee('Sin clasificar (antecedente desconocido)')->assertSee('Desconocido')
            ->assertSee('No hay antecedentes suficientes para acreditar el recorrido por esta fase.');
        $details = $response->viewData('phaseDetails');
        foreach (['planning', 'research'] as $slug) {
            $detail = $details->first(fn ($detail) => $detail['phase']->slug === $slug);
            $this->assertFalse((bool) $detail['known']);
            $this->assertNull($detail['progress']);
            $this->assertTrue($detail['activities']->isEmpty());
        }
        $future = $details->first(fn ($detail) => $detail['phase']->slug === 'edition');
        $this->assertTrue($future['future']);
        $this->assertNull($future['progress']);
        $current = $details->first(fn ($detail) => $detail['phase']->slug === 'experimentation');
        $this->assertSame(0.0, $current['progress']);
    }

    public function test_published_future_manual_has_no_fictional_followup_and_metadata_is_role_limited(): void
    {
        $manual = $this->manual('cgft', true);
        $manual->update(['published_on' => today()->addYear(), 'classification' => 'update']);
        $this->actor(['cgft']);
        $this->get(route('manuals.show', $manual))->assertOk()->assertSee('Actualización')
            ->assertSee('Fecha futura declarada; el manual ya está publicado.')->assertDontSee('Editar datos del manual')->assertDontSee('Registrar seguimiento');
        $this->actor(['publicados']);
        $this->get(route('manuals.show', $manual))->assertOk()->assertSee('Editar datos del manual')->assertDontSee('Registrar seguimiento');
    }

    public function test_current_participants_and_edition_responsible_are_shown_as_current_records(): void
    {
        $manual = $this->manual('edition');
        $grade = Grade::create(['code' => 88, 'grade_name' => 'Grado distinto']);
        $member = CommitteeMember::create(['grades_id' => $grade->id, 'full_name' => 'Integrante de ficha', 'identification' => '0012345678']);
        $manual->manualCommitteeMembers()->create(['committee_members_id' => $member->id, 'committee_type_id' => 1]);
        $responsible = User::factory()->create(['name' => 'Responsable conservado', 'is_active' => false]);
        $row = $manual->manualPhaseSuphases()->whereHas('catalogSubphase', fn ($query) => $query->where('activity_key', 'edition.editorial-layout'))->firstOrFail();
        $row->update(['responsible_user_id' => $responsible->id]);
        $this->actor(['supervisor']);
        $this->get(route('manuals.show', $manual))->assertOk()->assertSee('Integrante de ficha')->assertSee('Grado distinto')
            ->assertSee('Responsable conservado')->assertSee('(cuenta inactiva)')->assertSee('No acredita la composición de comités en fechas anteriores.');
    }

    public function test_general_events_are_paginated_and_do_not_expose_phase_notes_or_unknown_payload(): void
    {
        $manual = $this->manual();
        $actor = $this->actor(['supervisor']);
        ManualEvent::create(['manual_id' => $manual->id, 'user_id' => $actor->id, 'action' => 'phase_note', 'after' => ['phase_note' => 'NOTA_SECRETA_UNICA'], 'reason' => 'NOTA_SECRETA_UNICA']);
        for ($i = 0; $i < 25; $i++) {
            ManualEvent::create(['manual_id' => $manual->id, 'user_id' => $actor->id, 'action' => 'edited',
                'before' => ['manual_name' => 'Nombre anterior'], 'after' => ['manual_name' => 'Nombre vigente', 'phase_note' => 'NOTA_SECRETA_UNICA', 'phase_states' => [['note' => 'NOTA_SECRETA_UNICA']], 'password' => 'CAMPO_SENSIBLE'], 'reason' => 'NOTA_SECRETA_UNICA']);
        }
        $response = $this->get(route('manuals.show', $manual))->assertOk()->assertSee('Nombre anterior')->assertSee('Nombre vigente')
            ->assertDontSee('NOTA_SECRETA_UNICA')->assertDontSee('CAMPO_SENSIBLE')->assertSee('events_page=2', false);
        $this->assertSame(25, $response->viewData('events')->total());
        $this->assertCount(20, $response->viewData('events'));
        $this->get(route('manuals.show', ['id' => $manual->id, 'events_page' => 2]))->assertOk()->assertViewHas('events', fn ($events) => $events->count() === 5);
    }

    public function test_archived_followup_is_preserved_without_current_progress_weight(): void
    {
        $manual = $this->manual('edition');
        $archived = $manual->manualPhaseSuphases()->where('manual_phases_id', $manual->manual_phases_id)->firstOrFail();
        $archived->update(['is_legacy' => true, 'is_completed' => true, 'completation_date' => today()]);
        $this->actor(['supervisor']);
        $this->get(route('manuals.show', $manual))->assertOk()->assertSee('Seguimiento archivado conservado (1 actividades)')
            ->assertViewHas('manual', fn ($record) => $record->legacyActivities->count() === 1 && $record->manualPhaseSuphases->count() > 0);
    }

    public function test_event_fields_use_an_allowlist_and_escape_html(): void
    {
        $manual = $this->manual();
        $this->actor(['supervisor']);
        ManualEvent::create(['manual_id' => $manual->id, 'action' => 'edited', 'before' => [], 'after' => ['manual_name' => '<script>alert(1)</script>', 'note' => 'PRIVATE_NOTE']]);
        $this->get(route('manuals.show', $manual))->assertOk()->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)->assertDontSee('<script>alert(1)</script>', false)->assertDontSee('PRIVATE_NOTE');
    }

    public function test_visits_keep_reentries_unknown_exit_and_recorded_dates_distinct(): void
    {
        $manual = $this->manual();
        $this->actor(['supervisor']);
        $exit = ManualEvent::create(['manual_id' => $manual->id, 'action' => 'advance']);
        ManualPhaseVisit::create([
            'manual_id' => $manual->id, 'manual_phase_id' => $manual->manual_phases_id,
            'entered_on' => '2026-03-10', 'entered_recorded_at' => '2026-03-20 09:00:00',
            'exited_on' => null, 'exited_recorded_at' => '2026-03-25 10:00:00', 'exit_event_id' => $exit->id,
            'entry_basis' => 'phase_start', 'source' => 'reconstructed',
        ]);
        for ($i = 0; $i < 16; $i++) {
            ManualPhaseVisit::create([
                'manual_id' => $manual->id, 'manual_phase_id' => $manual->manual_phases_id,
                'entered_on' => null, 'entered_recorded_at' => '2026-04-01 09:00:00',
                'entry_basis' => 'unknown', 'source' => 'reconstructed',
            ]);
        }
        $before = [$manual->fresh()->lock_version, $manual->phaseStates()->count(), $manual->manualPhaseSuphases()->count(), $manual->events()->count()];
        $response = $this->get(route('manuals.show', $manual))->assertOk()->assertSee('10/03/2026')
            ->assertSee('20/03/2026 09:00')->assertSee('Salida acreditada, fecha desconocida')
            ->assertSee('Entrada efectiva desconocida')->assertSee('visits_page=2', false);
        $this->assertSame(17, $response->viewData('visits')->total());
        $this->assertCount(15, $response->viewData('visits'));
        $this->get(route('manuals.show', ['id' => $manual->id, 'visits_page' => 2]))->assertOk()->assertViewHas('visits', fn ($visits) => $visits->count() === 2);
        $this->assertSame($before, [$manual->fresh()->lock_version, $manual->phaseStates()->count(), $manual->manualPhaseSuphases()->count(), $manual->events()->count()]);
    }

    public function test_six_closed_edition_activities_keep_the_historical_denominator(): void
    {
        $manual = $this->manual('cgft');
        $phase = ManualPhase::where('slug', 'edition')->firstOrFail();
        $manual->phaseStates()->create(['manual_phase_id' => $phase->id, 'catalog_version' => 1, 'started_on' => today()->subMonth()]);
        for ($i = 1; $i <= 6; $i++) {
            $template = CatalogSubphase::create(['manual_phases_id' => $phase->id, 'activity_key' => 'edition.historical-'.$i, 'suphase_name' => 'Edición histórica conservada '.$i, 'position' => $i]);
            ManualPhaseSuphase::create(['manuals_id' => $manual->id, 'manual_phases_id' => $phase->id, 'catalog_subphases_id' => $template->id, 'is_completed' => true, 'completation_date' => today()->subDays(5)]);
        }
        $this->actor(['supervisor']);
        $response = $this->get(route('manuals.show', $manual))->assertOk()->assertSee('Catálogo histórico de Edición: seis actividades conservadas.')
            ->assertSee('Edición histórica conservada 6');
        $edition = $response->viewData('phaseDetails')->first(fn ($detail) => $detail['phase']->slug === 'edition');
        $this->assertSame(6, $edition['expected']);
        $this->assertSame(100.0, $edition['progress']);
        $this->assertCount(6, $edition['activities']);
    }
}
