<?php

namespace App\Services;

use App\Models\ManualPhase;
use App\Models\PhaseDuration;
use App\Models\PhaseDurationAudit;
use Illuminate\Support\Facades\DB;

class PhaseDurationService
{
    private ?array $durations = null;

    public function all(): array
    {
        return $this->durations ??= PhaseDuration::query()->pluck('duration_months', 'manual_phase_id')
            ->map(fn ($months) => (int) $months)->all();
    }

    public function forPhase(ManualPhase|string $phase): int
    {
        if (is_string($phase)) {
            $phase = ManualPhase::where('slug', $phase)->first();
        }
        if (! $phase) {
            return 3;
        }
        return $this->all()[$phase->id] ?? 3;
    }

    public function update(ManualPhase $phase, int $months, int $expectedOldMonths, ?int $userId): bool
    {
        if ($months < 1 || $months > 120 || $expectedOldMonths < 1 || $expectedOldMonths > 120) {
            throw new \InvalidArgumentException('La duración debe estar entre 1 y 120 meses.');
        }
        $changed = DB::transaction(function () use ($phase, $months, $expectedOldMonths, $userId) {
            $user = $userId ? \App\Models\User::whereKey($userId)->lockForUpdate()->first() : null;
            abort_unless($user && $user->is_active && $user->hasRole('admin'), 403);
            $duration = PhaseDuration::firstOrCreate(['manual_phase_id' => $phase->id], ['duration_months' => 3]);
            $duration = PhaseDuration::whereKey($duration->id)->lockForUpdate()->firstOrFail();
            $before = (int) $duration->duration_months;
            if ($before !== $expectedOldMonths) {
                return false;
            }
            if ($before !== $months) {
                $duration->update(['duration_months' => $months]);
                PhaseDurationAudit::create(['manual_phase_id' => $phase->id, 'user_id' => $userId,
                    'before_months' => $before, 'after_months' => $months]);
            }
            return true;
        });
        if ($changed) {
            $this->durations = null;
        }
        return $changed;
    }
}
