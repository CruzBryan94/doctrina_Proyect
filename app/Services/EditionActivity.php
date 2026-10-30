<?php

namespace App\Services;

use App\Models\ManualPhaseSuphase;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class EditionActivity
{
    public const START = 'edition.idiomatic-start';

    public const REVIEW = 'edition.idiomatic-final-review';

    public const LAYOUT = 'edition.editorial-layout';

    public const NAMES = [
        self::START => 'INICIAR CORRECCIÓN IDIOMÁTICA',
        self::REVIEW => 'CORRECCIÓN IDIOMÁTICA REVISIÓN FINAL',
        self::LAYOUT => 'CORRECCIÓN EDITORIAL Y GRÁFICA / MAQUETACIÓN',
    ];

    public static function fields(string $key): array
    {
        return match ($key) {
            self::START => ['follow_up'], self::REVIEW => ['follow_up', 'due_on'],
            self::LAYOUT => ['started_on', 'due_on', 'responsible_user_id'], default => [],
        };
    }

    public static function validate(ManualPhaseSuphase $row, array $data, $phaseStart): void
    {
        $allowed = self::fields($row->catalogSubphase->activity_key);
        foreach (['follow_up', 'started_on', 'due_on', 'responsible_user_id'] as $field) {
            if (array_key_exists($field, $data)) {
                if (! in_array($field, $allowed, true)) {
                    throw ValidationException::withMessages([$field => 'Este campo no corresponde a esta actividad.']);
                }
                if ($field === 'responsible_user_id' && $data[$field] && (int) $data[$field] !== (int) $row->responsible_user_id && ! User::whereKey($data[$field])->where('is_active', true)->exists()) {
                    throw ValidationException::withMessages([$field => 'Selecciona un usuario activo como responsable.']);
                }
                $row->$field = $data[$field];
            }
        }
        if (($row->started_on && $phaseStart && $row->started_on->lt($phaseStart)) || ($row->due_on && ($row->started_on ?? $phaseStart) && $row->due_on->lt($row->started_on ?? $phaseStart))) {
            throw ValidationException::withMessages(['due_on' => 'Las fechas deben respetar el inicio de Edición y el inicio de la actividad.']);
        }
        if ($row->is_completed && (! self::requirementsMet($row) || ! $row->completation_date || ! $phaseStart || $row->completation_date->lt($row->started_on ?? $phaseStart))) {
            throw ValidationException::withMessages(['activity' => 'Para completar la actividad registra sus campos obligatorios y una fecha de cumplimiento válida.']);
        }
    }

    public static function requirementsMet(ManualPhaseSuphase $row): bool
    {
        foreach (self::fields($row->catalogSubphase->activity_key) as $field) {
            if ($row->$field === null || (is_string($row->$field) && trim($row->$field) === '')) {
                return false;
            }
        }

        return ! $row->started_on || ($row->due_on && $row->due_on->gte($row->started_on) && (! $row->is_completed || ($row->completation_date && $row->completation_date->gte($row->started_on))));
    }
}
