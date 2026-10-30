<?php

namespace Tests\Feature;

use App\Models\CatalogSubphase;
use App\Models\Manual;
use App\Models\ManualPhase;
use App\Models\ManualType;
use App\Models\User;
use App\Services\ManualWorkflow;
use Database\Seeders\CatalogSubphasesSeeder;
use Database\Seeders\ManualPhasesSeeder;
use Database\Seeders\ManualTypesSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class VariableActivityCatalogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([ManualPhasesSeeder::class, CatalogSubphasesSeeder::class, ManualTypesSeeder::class, RoleSeeder::class]);
        $actor = User::factory()->create();
        $actor->assignRole('admin');
        $this->actingAs($actor);
    }

    private function manual(string $slug, int $research = 2, int $experimentation = 2): Manual
    {
        $manual = Manual::create([
            'manual_name' => 'Catálogo aislado', 'manual_types_id' => ManualType::first()->id,
            'manual_phases_id' => ManualPhase::where('slug', $slug)->value('id'),
            'research_advances' => $research, 'experimentation_advances' => $experimentation,
            'process_started_on' => today()->subDays(10), 'is_active' => true, 'is_published' => false,
        ]);
        app(ManualWorkflow::class)->configure($manual);
        $manual->phaseStates()->update(['started_on' => today()->subDays(10)]);

        return $manual->fresh()->load(ManualWorkflow::RELATIONS);
    }

    public function test_current_catalog_has_35_templates_and_stable_final_keys(): void
    {
        $this->assertSame(35, CatalogSubphase::where('is_active', true)->count());
        $research = ManualPhase::where('slug', 'research')->first();
        $keys = $research->catalogSubphases->pluck('activity_key')->all();
        $this->assertSame(['research.validation-meeting.3', 'research.validation-meeting.4', 'research.14'], array_slice($keys, -3));
        $this->assertDatabaseHas('catalog_subphases', ['activity_key' => 'experimentation.12', 'suphase_name' => 'INFORME FINAL DE LA EXPERIMENTACIÓN', 'position' => 9]);
        $this->assertDatabaseHas('catalog_subphases', ['activity_key' => 'experimentation.14', 'position' => 11]);
    }

    public function test_applicable_denominators_and_fourth_meeting_are_mandatory(): void
    {
        foreach ([[2, 2, 13, 8, 28], [6, 5, 17, 11, 35]] as [$research, $experimentation, $expectedResearch, $expectedExperimentation, $total]) {
            $manual = $this->manual('research', $research, $experimentation);
            $this->assertSame($expectedResearch, $manual->expectedActivities('research'));
            $this->assertSame($expectedExperimentation, $manual->expectedActivities('experimentation'));
            $this->assertCount($total, $manual->manualPhaseSuphases);
            $fourth = $manual->manualPhaseSuphases->first(fn ($row) => $row->catalogSubphase->activity_key === 'research.validation-meeting.4');
            $manual->manualPhaseSuphases()->where('manual_phases_id', $manual->manual_phases_id)->where('id', '!=', $fourth->id)->update(['is_completed' => true, 'completation_date' => today()]);
            $this->assertLessThan(100, $manual->fresh()->load(ManualWorkflow::RELATIONS)->progress());
            $this->patchJson(route('manuals.activity', $manual->id), ['action' => 'advance', 'confirmed' => true, 'version' => 0])->assertUnprocessable()->assertJsonValidationErrors('phase');
        }
    }

    private function legacyManual(string $slug): Manual
    {
        $manual = $this->manual($slug, 2, 8);
        $fourth = CatalogSubphase::where('activity_key', 'research.validation-meeting.4')->value('id');
        $manual->manualPhaseSuphases()->where('catalog_subphases_id', $fourth)->delete(); // Build a pre-upgrade fixture only in SQLite memory.
        $manual->phaseStates()->whereIn('manual_phase_id', ManualPhase::whereIn('slug', ['research', 'experimentation'])->pluck('id'))->update(['catalog_version' => 2]);
        $exp = ManualPhase::where('slug', 'experimentation')->first();
        foreach ([6, 7, 8] as $number) {
            $catalog = CatalogSubphase::firstOrCreate(['activity_key' => 'experimentation.'.($number + 3)], ['manual_phases_id' => $exp->id, 'suphase_name' => 'AVANCE LEGADO '.$number, 'position' => $number + 3, 'advance_number' => $number, 'is_active' => true]);
            $manual->manualPhaseSuphases()->firstOrCreate(['catalog_subphases_id' => $catalog->id], ['manual_phases_id' => $exp->id, 'is_completed' => false]);
        }
        $manual->manualPhaseSuphases()->whereIn('manual_phases_id', ManualPhase::whereIn('slug', ['research', 'experimentation'])->pluck('id'))->update(['is_completed' => true, 'completation_date' => today()->subDay()]);

        return $manual->fresh()->load(ManualWorkflow::RELATIONS);
    }

    public function test_prepared_migration_archives_evidence_without_rewriting_closed_history(): void
    {
        $open = $this->legacyManual('research');
        $closed = $this->legacyManual('cgft');
        $beforeClosed = DB::table('manual_phase_suphases')->where('manuals_id', $closed->id)->orderBy('id')->get()->toArray();
        $beforeOpen = DB::table('manual_phase_suphases')->where('manuals_id', $open->id)->orderBy('id')->get();
        $catalogIds = CatalogSubphase::pluck('id', 'activity_key');
        $migration = require database_path('migrations/2026_10_24_000002_upgrade_variable_activity_catalogs.php');
        $migration->up();
        $open = $open->fresh()->load(ManualWorkflow::RELATIONS);
        $closed = $closed->fresh()->load(ManualWorkflow::RELATIONS);
        $this->assertEquals($beforeClosed, DB::table('manual_phase_suphases')->where('manuals_id', $closed->id)->orderBy('id')->get()->toArray());
        $this->assertSame(8, $closed->experimentation_advances);
        $this->assertSame(12, $closed->expectedActivities('research'));
        $this->assertSame(14, $closed->expectedActivities('experimentation'));
        $this->assertEquals(100, $closed->progress(ManualPhase::where('slug', 'research')->value('id')));
        $this->assertSame(5, $open->experimentation_advances);
        $this->assertCount(3, $open->legacyActivities);
        $this->assertTrue($open->legacyActivities->every(fn ($row) => $row->is_completed && $row->completation_date->isSameDay(today()->subDay())));
        $fourth = $open->manualPhaseSuphases->first(fn ($row) => $row->catalogSubphase->activity_key === 'research.validation-meeting.4');
        $this->assertFalse($fourth->is_completed);
        $this->assertNull($fourth->completation_date);
        foreach ($beforeOpen as $row) {
            $after = DB::table('manual_phase_suphases')->find($row->id);
            $this->assertSame($row->is_completed, $after->is_completed);
            $this->assertSame($row->completation_date, $after->completation_date);
        }
        foreach ($catalogIds as $key => $id) {
            $this->assertSame($id, CatalogSubphase::where('activity_key', $key)->value('id'));
        }
        $this->assertSame(35, CatalogSubphase::where('is_active', true)->count());
        $this->assertSame(2, DB::table('manual_events')->where('action', 'variable_catalog_updated')->count());
        $this->assertCount(12, $open->events()->where('action', 'variable_catalog_updated')->first()->before['phases']['research']['activities']);
        $version = $open->lock_version;
        $migration->up();
        $this->assertSame($version, $open->fresh()->lock_version);
        $this->assertSame(2, DB::table('manual_events')->where('action', 'variable_catalog_updated')->count());
    }

    public function test_reopening_a_closed_legacy_phase_uses_current_catalog_and_preserves_retired_rows(): void
    {
        $manual = $this->legacyManual('edition');
        $phase = ManualPhase::where('slug', 'experimentation')->first();
        $manual->manual_phases_id = $phase->id;
        $manual->save();
        app(ManualWorkflow::class)->reopenVariablePhase($manual, $phase);
        $manual = $manual->fresh()->load(ManualWorkflow::RELATIONS);
        $this->assertSame(5, $manual->experimentation_advances);
        $this->assertSame(11, $manual->expectedActivities('experimentation'));
        $this->assertCount(3, $manual->legacyActivities);
        $this->assertDatabaseHas('manual_events', ['manual_id' => $manual->id, 'action' => 'variable_catalog_reopened']);
    }
}
