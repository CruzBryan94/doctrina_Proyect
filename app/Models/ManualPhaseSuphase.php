<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ManualPhaseSuphase extends Model
{
    protected $fillable = ['manuals_id', 'catalog_subphases_id', 'manual_phases_id', 'is_completed', 'completation_date', 'follow_up', 'started_on', 'due_on', 'responsible_user_id', 'is_legacy'];

    protected $casts = ['is_completed' => 'boolean', 'is_legacy' => 'boolean', 'completation_date' => 'date', 'started_on' => 'date', 'due_on' => 'date'];

    public function responsibleUser()
    {
        return $this->belongsTo(User::class, 'responsible_user_id');
    }

    public function isWorkflowComplete(): bool
    {
        return $this->is_completed && $this->completation_date && \App\Services\EditionActivity::requirementsMet($this);
    }

    public function deadlineStatus(): ?string
    {
        if (! $this->due_on) {
            return null;
        }

        return $this->is_completed ? ($this->completation_date?->gt($this->due_on) ? 'Fuera de plazo' : null) : (today()->gt($this->due_on) ? 'Vencida' : null);
    }

    public function scopeCompletedForWorkflow($query)
    {
        $keys = \App\Services\EditionActivity::class;

        return $query->where('is_completed', true)->whereNotNull('completation_date')->where(function ($q) use ($keys) {
            $q->whereDoesntHave('catalogSubphase', fn ($catalog) => $catalog->whereIn('activity_key', array_keys($keys::NAMES)))
                ->orWhere(function ($q) use ($keys) {
                    $q->whereHas('catalogSubphase', fn ($c) => $c->where('activity_key', $keys::START))->whereNotNull('follow_up')->whereRaw("TRIM(follow_up) <> ''");
                })->orWhere(function ($q) use ($keys) {
                    $q->whereHas('catalogSubphase', fn ($c) => $c->where('activity_key', $keys::REVIEW))->whereNotNull('follow_up')->whereRaw("TRIM(follow_up) <> ''")->whereNotNull('due_on');
                })->orWhere(function ($q) use ($keys) {
                    $q->whereHas('catalogSubphase', fn ($c) => $c->where('activity_key', $keys::LAYOUT))->whereNotNull('started_on')->whereNotNull('due_on')->whereNotNull('responsible_user_id')->whereColumn('due_on', '>=', 'started_on')->whereColumn('completation_date', '>=', 'started_on');
                });
        });
    }

    public function catalogSubphase()
    {
        return $this->belongsTo(CatalogSubphase::class, 'catalog_subphases_id');
    }

    public function manual()
    {
        return $this->belongsTo(Manual::class, 'manuals_id');
    }
}
