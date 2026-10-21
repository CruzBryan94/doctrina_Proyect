<?php

namespace Database\Seeders;

use App\Models\CommitteeMember;
use App\Models\Manual;
use App\Models\ManualCommitteeMember;
use App\Models\ManualMilitaryUnit;
use App\Models\ManualPhase;
use App\Models\ManualType;
use App\Models\User;
use App\Services\ManualWorkflow;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['supervisor', 'planificador', 'investigador', 'experimentador', 'edición', 'cgft', 'none'] as $role) {
            $login = $role === 'edición' ? 'edicion' : $role;
            $user = User::updateOrCreate(['email' => $login.'@artemis.test'], [
                'name' => 'Prueba '.ucfirst($role), 'password' => Hash::make('Prueba123!'), 'grade' => 17, 'is_active' => true,
            ]);
            $user->syncRoles([$role]);
        }
        foreach (['planificacion-investigacion' => ['planificador', 'investigador'], 'investigacion-experimentacion' => ['investigador', 'experimentador']] as $login => $roles) {
            $user = User::updateOrCreate(['email' => $login.'@artemis.test'], [
                'name' => 'Prueba varios roles', 'password' => Hash::make('Prueba123!'), 'grade' => 12, 'is_active' => true,
            ]);
            $user->syncRoles($roles);
        }
        foreach (['Andrés Torres', 'María Rojas', 'Carlos Vera', 'Lucía Andrade', 'Diego Molina', 'Elena Cárdenas', 'Javier López', 'Daniela Paz', 'Luis Salazar', 'Ana Rivera', 'Pedro Suárez', 'Sofía Castillo'] as $index => $name) {
            CommitteeMember::updateOrCreate(['identification' => '990000'.str_pad($index + 1, 4, '0', STR_PAD_LEFT)], [
                'full_name' => $name.' (prueba)', 'grades_id' => 10 + ($index % 7), 'cellphone' => '099000'.str_pad($index + 1, 4, '0', STR_PAD_LEFT),
            ]);
        }
        $workflow = app(ManualWorkflow::class);
        $phases = ManualPhase::orderBy('position')->get();
        $types = ManualType::orderBy('id')->get();
        $names = ['Operaciones terrestres', 'Apoyo logístico', 'Protección de la fuerza', 'Formación y entrenamiento'];
        foreach ($phases as $phase) {
            for ($index = 0; $index < 4; $index++) {
                $this->manual($workflow, $phases, $types[($phase->position * 2 + $index) % $types->count()]->id, $phase, $index, $names[$index], false);
            }
        }
        for ($index = 0; $index < 4; $index++) {
            $this->manual($workflow, $phases, $types[$index]->id, $phases->last(), $index, 'Publicación de referencia '.($index + 1), true);
        }
    }

    private function manual($workflow, $phases, int $typeId, $current, int $index, string $name, bool $published): void
    {
        $base = today()->subDays($index === 3 ? 570 : ($index === 2 ? 220 : 55));
        $manual = Manual::create([
            'manual_name' => $name.' · '.$current->phase_name.($published ? ' · Publicado' : ''),
            'manual_types_id' => $typeId, 'manual_phases_id' => $current->id,
            'research_advances' => [2, 4, 6, 8][$index], 'experimentation_advances' => [3, 5, 8, 2][$index],
            'process_started_on' => $base, 'observations' => 'Dato sintético para pruebas funcionales. No corresponde a un manual real.',
            'is_active' => true, 'is_published' => $published,
        ]);
        $workflow->configure($manual);
        $manual->load(ManualWorkflow::RELATIONS);
        foreach ($phases as $phase) {
            if ($phase->position > $current->position) {
                continue;
            }
            $start = $base->copy()->addDays(($phase->position - 1) * 7);
            $manual->phaseStates->firstWhere('manual_phase_id', $phase->id)->update(['started_on' => $start]);
            if ($phase->slug === 'research') {
                $manual->research_started_on = $start;
            }
            $rows = $manual->manualPhaseSuphases->where('manual_phases_id', $phase->id)->values();
            $completed = $phase->position < $current->position || $published ? $rows->count() : min($index, $rows->count() - 1);
            foreach ($rows as $rowIndex => $row) {
                if ($rowIndex < $completed) {
                    $row->update(['is_completed' => true, 'completation_date' => $start->copy()->addDays(min($rowIndex, 5))]);
                }
            }
            $committeeTypes = match ($phase->slug) {
                'research' => [1, 2], 'experimentation' => [3, 4], default => []
            };
            foreach ($committeeTypes as $committee) {
                $ids = CommitteeMember::orderBy('id')->skip(($index * 2) % 10)->take(2)->pluck('id');
                foreach ($ids as $memberId) {
                    ManualCommitteeMember::create(['manuals_id' => $manual->id, 'committee_type_id' => $committee, 'committee_members_id' => $memberId]);
                }
                if (in_array($committee, [1, 3, 4], true)) {
                    ManualMilitaryUnit::create(['manuals_id' => $manual->id, 'committee_type_id' => $committee, 'military_units_id' => 1 + ($index % 3)]);
                }
            }
        }
        if ($published) {
            $manual->published_on = $manual->manualPhaseSuphases()->where('manual_phases_id', $current->id)->max('completation_date');
        }
        $manual->save();
        $workflow->synchronizeSubstates($manual->load(ManualWorkflow::RELATIONS));
        $workflow->event($manual, 'created', [], ['phase' => $current->slug, 'published' => $published], 'Escenario de prueba generado por DemoSeeder');
    }
}
