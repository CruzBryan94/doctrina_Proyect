<?php

namespace App\Services;

use App\Models\Manual;
use App\Models\ManualEvent;
use App\Models\ManualPhase;
use App\Models\ManualPhaseVisit;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ManualPhaseHistory
{
    /** Called inside the same transaction and manual lock as the workflow event. */
    public function record(Manual $manual, ManualEvent $event, string $source = 'live'): void
    {
        if ($event->action === 'created') {
            if ($event->after['is_published'] ?? false) {
                return; // Direct publication does not imply a CGFT stay.
            }
            $phase = $this->phase($event->after['phase'] ?? null, $event->after['manual_phases_id'] ?? null);
            if (! $phase) {
                return;
            }
            $date = $this->date($event->after['phase_started_on'] ?? null);
            // Only Planning can use a known process start as a phase start.
            if (! $date && $phase->slug === 'planning') {
                $date = $this->date($event->after['process_started_on'] ?? null);
            }
            if ($source === 'live') {
                $date ??= $manual->currentStart()?->toDateString();
            }
            $this->enter($manual, $phase, $event, $date, $date ? 'phase_start' : 'unknown', $source);

            return;
        }

        if ($event->action === 'start') {
            $phase = $this->phase($event->after['phase'] ?? null);
            if (! $phase) {
                return;
            }
            $visit = $this->open($manual, $phase);
            $date = $this->date($event->after['started_on'] ?? null);
            if (! $visit) {
                $this->enter($manual, $phase, $event, $date, $date ? 'phase_start' : 'unknown', $source);
            } elseif ($visit->entry_basis !== 'transition') {
                $visit->update(['entered_on' => $date, 'entry_basis' => $date ? 'phase_start' : 'unknown']);
            }

            return;
        }

        $oldSlug = $event->before['phase'] ?? null;
        $newSlug = $event->after['phase'] ?? null;
        $wasPublished = (bool) ($event->before['published'] ?? false);
        $isPublished = (bool) ($event->after['published'] ?? false);
        if (! in_array($event->action, ['advance', 'retreat', 'activity'], true) || ! $oldSlug || ! $newSlug || ($oldSlug === $newSlug && $wasPublished === $isPublished)) {
            return;
        }
        $from = $this->phase($oldSlug);
        $to = $this->phase($newSlug);
        if (! $from || ! $to) {
            return;
        }
        $date = $this->date($event->after['effective_on'] ?? null);
        if (! $wasPublished) {
            $visit = $this->open($manual, $from);
            // A transition proves departure, but not an otherwise undocumented arrival.
            $visit ??= $this->enter($manual, $from, null, null, 'unknown', $source, $event->created_at);
            $visit->update(['exited_on' => $date, 'exited_recorded_at' => $event->created_at, 'exit_event_id' => $event->id]);
        }
        if (! $isPublished) {
            $this->enter($manual, $to, $event, $date, $date ? 'transition' : 'unknown', $source);
        }
    }

    private function phase(?string $slug, ?int $id = null): ?ManualPhase
    {
        return $slug ? ManualPhase::where('slug', $slug)->first() : ($id ? ManualPhase::find($id) : null);
    }

    private function date($value): ?string
    {
        if (! is_string($value) || strlen($value) < 10) {
            return null;
        }
        $value = substr($value, 0, 10);
        try {
            return Carbon::createFromFormat('!Y-m-d', $value)->format('Y-m-d') === $value ? $value : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function open(Manual $manual, ManualPhase $phase): ?ManualPhaseVisit
    {
        return ManualPhaseVisit::where('manual_id', $manual->id)->where('manual_phase_id', $phase->id)->whereNull('exit_event_id')->latest('id')->lockForUpdate()->first();
    }

    private function enter(Manual $manual, ManualPhase $phase, ?ManualEvent $event, ?string $date, string $basis, string $source, $recordedAt = null): ManualPhaseVisit
    {
        if ($event && ($existing = ManualPhaseVisit::where('entry_event_id', $event->id)->first())) {
            return $existing;
        }

        return ManualPhaseVisit::create([
            'manual_id' => $manual->id, 'manual_phase_id' => $phase->id, 'entered_on' => $date,
            'entered_recorded_at' => $event?->created_at ?? $recordedAt ?? now(), 'entry_event_id' => $event?->id,
            'entry_basis' => $basis, 'source' => $source,
        ]);
    }

    /** Replay only event evidence. Existing live or reconstructed visits are never overwritten. */
    public function reconstruct(Manual $manual, bool $apply = false): array
    {
        return DB::transaction(function () use ($manual, $apply) {
            Manual::whereKey($manual->id)->lockForUpdate()->firstOrFail();
            if (ManualPhaseVisit::where('manual_id', $manual->id)->exists()) {
                return ['manual_id' => $manual->id, 'status' => 'already_recorded', 'events' => 0];
            }
            $events = $manual->events()->reorder('id')->get();
            $evidence = $events->whereIn('action', ['created', 'start', 'advance', 'retreat', 'activity']);
            if ($apply) {
                foreach ($evidence as $event) {
                    $this->record($manual, $event, 'reconstructed');
                }
            }

            return ['manual_id' => $manual->id, 'status' => $apply ? 'reconstructed' : 'preview', 'events' => $evidence->count()];
        });
    }
}
