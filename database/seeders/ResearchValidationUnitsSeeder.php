<?php

namespace Database\Seeders;

use App\Models\CommitteeType;
use App\Models\Manual;
use App\Models\ManualMilitaryUnit;
use App\Models\MilitaryUnit;
use App\Services\ManualWorkflow;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ResearchValidationUnitsSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            return;
        }
        $type = CommitteeType::where('code', 2)->first();
        if (! $type) {
            return;
        }
        DB::transaction(function () use ($type) {
            $unit = MilitaryUnit::firstOrCreate(['unit_acronym' => 'PR-VAL-INV'], ['unit_name' => 'Unidad de Validación de Investigación (PRUEBA)']);
            Manual::whereHas('manualCommitteeMembers', fn ($q) => $q->where('committee_type_id', $type->id))
                ->whereHas('manualPhase', fn ($q) => $q->where('slug', '!=', 'planning'))
                ->whereDoesntHave('manualMilitaryUnits', fn ($q) => $q->where('committee_type_id', $type->id))
                ->orderBy('id')->lockForUpdate()->chunkById(100, function ($manuals) use ($unit, $type) {
                    foreach ($manuals as $manual) {
                        ManualMilitaryUnit::firstOrCreate(['manuals_id' => $manual->id, 'committee_type_id' => $type->id, 'military_units_id' => $unit->id]);
                        $manual->lock_version++;
                        $manual->save();
                        app(ManualWorkflow::class)->event($manual, 'validation_units_seeded', ['units' => []], ['committee_type_id' => $type->id, 'units' => [$unit->id]], 'Unidad ficticia para probar Validación de Investigación. Asignación opcional.');
                    }
                });
        });
    }
}
