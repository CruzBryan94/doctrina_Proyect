<?php

namespace Database\Seeders;

use App\Models\ManualPhase;
use Illuminate\Database\Seeder;

class ManualPhasesSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['planning' => 'Planificación', 'research' => 'Investigación', 'experimentation' => 'Experimentación', 'edition' => 'Edición', 'cgft' => 'CGFT'] as $slug => $name) {
            $position = array_search($slug, array_keys(ManualPhase::ROLES), true) + 1;
            ManualPhase::updateOrCreate(['slug' => $slug], ['code' => $position, 'position' => $position, 'phase_name' => $name, 'is_active' => true]);
        }
    }
}
