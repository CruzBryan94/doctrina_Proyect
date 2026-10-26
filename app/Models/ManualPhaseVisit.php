<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ManualPhaseVisit extends Model
{
    protected $fillable = ['manual_id', 'manual_phase_id', 'entered_on', 'exited_on', 'entered_recorded_at', 'exited_recorded_at', 'entry_event_id', 'exit_event_id', 'entry_basis', 'source'];

    protected $casts = ['entered_on' => 'date', 'exited_on' => 'date', 'entered_recorded_at' => 'datetime', 'exited_recorded_at' => 'datetime'];

    public function manual()
    {
        return $this->belongsTo(Manual::class);
    }

    public function phase()
    {
        return $this->belongsTo(ManualPhase::class, 'manual_phase_id');
    }

    public function entryEvent()
    {
        return $this->belongsTo(ManualEvent::class, 'entry_event_id');
    }

    public function exitEvent()
    {
        return $this->belongsTo(ManualEvent::class, 'exit_event_id');
    }

    public function scopeOverlappingPeriod($query, string $from, string $to)
    {
        $lastKnownDay = min($to, today()->toDateString());

        return $query->whereNotNull('entered_on')->whereDate('entered_on', '<=', $lastKnownDay)
            ->where(function ($q) use ($from) {
                $q->whereDate('exited_on', '>=', $from)
                    ->orWhere(fn ($open) => $open->whereNull('exit_event_id')->whereDate('entered_on', '<=', today()))
                    ->orWhere(fn ($unknown) => $unknown->whereNotNull('exit_event_id')->whereNull('exited_on')->whereDate('entered_on', '>=', $from));
            })->whereRaw('? <= ?', [$from, $lastKnownDay]);
    }
}
