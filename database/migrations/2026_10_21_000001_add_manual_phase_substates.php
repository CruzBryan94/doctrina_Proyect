<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('manual_phase_states', function (Blueprint $table) {
            $table->string('substate', 20)->nullable()->index();
            $table->date('validation_started_on')->nullable();
        });
        // Reconstruct existing states from recorded dates, without inventing a start.
        DB::table('manual_phase_states as states')
            ->join('manual_phases as phases', 'phases.id', '=', 'states.manual_phase_id')
            ->whereIn('phases.slug', ['research', 'experimentation'])
            ->select('states.id', 'states.manual_id', 'states.manual_phase_id', 'states.started_on', 'phases.slug')
            ->orderBy('states.id')->chunkById(200, function ($states) {
                foreach ($states as $state) {
                    $boundary = DB::table('manual_phase_suphases as tracking')
                        ->join('catalog_subphases as catalog', 'catalog.id', '=', 'tracking.catalog_subphases_id')
                        ->where('tracking.manuals_id', $state->manual_id)
                        ->where('tracking.manual_phases_id', $state->manual_phase_id)
                        ->where('catalog.activity_key', $state->slug.'.12')
                        ->where('tracking.is_completed', true)->value('tracking.completation_date');
                    DB::table('manual_phase_states')->where('id', $state->id)->update([
                        'substate' => $state->started_on ? ($boundary ? 'validation' : 'development') : null,
                        'validation_started_on' => $state->started_on ? $boundary : null,
                    ]);
                }
            }, 'states.id', 'id');
    }

    public function down(): void
    {
        Schema::table('manual_phase_states', function (Blueprint $table) {
            $table->dropIndex(['substate']);
            $table->dropColumn(['substate', 'validation_started_on']);
        });
    }
};
