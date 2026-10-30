<?php

namespace App\Services;

use App\Models\Manual;
use App\Models\ManualPhase;
use App\Models\ManualPhaseNote;
use App\Models\ManualPhaseNoteAudit;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class ManualPhaseNotes
{
    public function canView(User $user): bool
    {
        return $user->canConsultManuals();
    }

    public function canEdit(User $user, ManualPhase $phase): bool
    {
        return $user->is_active && ($user->hasRole('admin')
            || in_array(ManualPhase::ROLES[$phase->slug] ?? null, $user->getRoleNames()->all(), true));
    }

    public function save(int $manualId, int $phaseId, User $actor, mixed $expectedVersion, mixed $note): Manual
    {
        return DB::transaction(function () use ($manualId, $phaseId, $actor, $expectedVersion, $note) {
            $manual = Manual::query()->whereKey($manualId)->lockForUpdate()->firstOrFail();
            $phase = ManualPhase::query()->whereKey($phaseId)->firstOrFail();

            if (! $this->canEdit($actor, $phase)) {
                throw new AccessDeniedHttpException();
            }

            if (filter_var($expectedVersion, FILTER_VALIDATE_INT) === false || (int) $expectedVersion !== (int) $manual->lock_version) {
                throw new ConflictHttpException('El manual cambió desde que abriste esta nota. Recarga para conservar los cambios más recientes.');
            }

            $validator = Validator::make(['note' => $note], ['note' => ['nullable', 'string', 'max:1000']]);
            if ($validator->fails()) {
                throw new ValidationException($validator);
            }
            $validated = $validator->validated();
            $value = isset($validated['note']) && trim($validated['note']) !== '' ? $validated['note'] : null;

            $record = ManualPhaseNote::query()->where('manual_id', $manual->id)->where('manual_phase_id', $phase->id)->first();
            $before = $record?->note;
            if ($before !== $value) {
                if ($record) {
                    $record->update(['note' => $value]);
                } else {
                    $record = ManualPhaseNote::create(['manual_id' => $manual->id, 'manual_phase_id' => $phase->id, 'note' => $value]);
                }

                ManualPhaseNoteAudit::create([
                    'manual_id' => $manual->id, 'manual_phase_id' => $phase->id,
                    'user_id' => $actor->id, 'before_note' => $before, 'after_note' => $value,
                ]);
                $manual->lock_version = ((int) $manual->lock_version) + 1;
                $manual->save();
            }

            return $manual->fresh();
        });
    }
}
