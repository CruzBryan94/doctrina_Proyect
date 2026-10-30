<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('phase_durations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('manual_phase_id')->unique()->constrained('manual_phases')->cascadeOnDelete();
            $table->unsignedSmallInteger('duration_months')->default(3);
            $table->timestamps();
        });
        Schema::create('phase_duration_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('manual_phase_id')->constrained('manual_phases')->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedSmallInteger('before_months');
            $table->unsignedSmallInteger('after_months');
            $table->timestamps();
        });

        $now = now();
        $rows = DB::table('manual_phases')->pluck('id')->map(fn ($id) => [
            'manual_phase_id' => $id, 'duration_months' => 3, 'created_at' => $now, 'updated_at' => $now,
        ])->all();
        if ($rows) {
            DB::table('phase_durations')->insert($rows);
        }
    }

    public function down(): void
    {
        throw new LogicException('Las duraciones y su auditoría forman parte de la trazabilidad; la reversión automática está deshabilitada.');
    }
};
