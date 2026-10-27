<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class ManualProductivity
{
    public function manuals(array $filters): Builder
    {
        $query = DB::table('manuals')->join('manual_types as productivity_type', 'productivity_type.id', '=', 'manuals.manual_types_id')
            ->leftJoin('doctrinal_maps as productivity_map', 'productivity_map.id', '=', 'productivity_type.doctrinal_map_id')
            ->where('manuals.is_active', true);
        if (! empty($filters['q'])) {
            $query->where('manuals.manual_name', 'like', '%'.$filters['q'].'%');
        }
        if (! empty($filters['map'])) {
            $query->where('productivity_type.doctrinal_map_id', $filters['map']);
        }
        if (! empty($filters['type'])) {
            $query->where('manuals.manual_types_id', $filters['type']);
        }
        if (($filters['classification'] ?? '') === 'unknown') {
            $query->whereNull('manuals.classification');
        } elseif (! empty($filters['classification'])) {
            $query->where('manuals.classification', $filters['classification']);
        }

        return $query;
    }

    public function visits(array $filters): Builder
    {
        return $this->manuals($filters)->join('manual_phase_visits as productivity_visit', 'productivity_visit.manual_id', '=', 'manuals.id')
            ->join('manual_phases as productivity_phase', 'productivity_phase.id', '=', 'productivity_visit.manual_phase_id');
    }

    public function publications(array $filters, string $from, string $to): Builder
    {
        return $this->manuals($filters)->where('manuals.is_published', true)->whereDate('manuals.published_on', '>=', $from)->whereDate('manuals.published_on', '<=', $to);
    }

    public function presence(array $filters, string $from, string $to): Builder
    {
        $end = min($to, today()->toDateString());

        return $this->visits($filters)->whereRaw('? <= ?', [$from, $end])
            ->whereNotNull('productivity_visit.entered_on')->where('productivity_visit.entered_on', '<=', $end)
            ->where(function ($q) use ($from) {
                $q->where('productivity_visit.exited_on', '>=', $from)
                    ->orWhere(fn ($q) => $q->whereNull('productivity_visit.exited_on')->whereNull('productivity_visit.exit_event_id'))
                    ->orWhere(fn ($q) => $q->whereNull('productivity_visit.exited_on')->whereNotNull('productivity_visit.exit_event_id')->where('productivity_visit.entered_on', '>=', $from));
            });
    }

    public function cgftTransfers(array $filters, string $from, string $to): Builder
    {
        return $this->visits($filters)->join('manual_events as productivity_event', 'productivity_event.id', '=', 'productivity_visit.entry_event_id')
            ->where('productivity_phase.slug', 'cgft')->where('productivity_visit.entry_basis', 'transition')
            ->where('productivity_event.action', 'advance')->whereBetween('productivity_visit.entered_on', [$from, min($to, today()->toDateString())]);
    }

    public function summary(array $filters, string $from, string $to): array
    {
        $phaseCounts = $this->presence($filters, $from, $to)->select('productivity_phase.slug')
            ->selectRaw('COUNT(DISTINCT manuals.id) as quantity')->groupBy('productivity_phase.slug')->pluck('quantity', 'slug');

        return [
            'publications' => $this->publications($filters, $from, $to)->count('manuals.id'),
            'cgft' => $this->cgftTransfers($filters, $from, $to)->distinct()->count('manuals.id'),
            'phases' => $phaseCounts,
        ];
    }

    public function monthlySeries(array $filters, string $from, string $to): array
    {
        $publicationQuery = $this->publications($filters, $from, $to);
        $publicationRows = (clone $publicationQuery)
            ->selectRaw('SUBSTR(manuals.published_on, 1, 7) as month_key, COUNT(*) as quantity')
            ->groupBy('month_key')->pluck('quantity', 'month_key');
        $futurePublicationRows = (clone $publicationQuery)->whereDate('manuals.published_on', '>', today()->toDateString())
            ->selectRaw('SUBSTR(manuals.published_on, 1, 7) as month_key, COUNT(*) as quantity')
            ->groupBy('month_key')->pluck('quantity', 'month_key');
        $transferRows = $this->cgftTransfers($filters, $from, $to)
            ->selectRaw('SUBSTR(productivity_visit.entered_on, 1, 7) as month_key, COUNT(DISTINCT manuals.id) as quantity')
            ->groupBy('month_key')->pluck('quantity', 'month_key');

        $months = [];
        $cursor = CarbonImmutable::parse($from)->startOfMonth();
        $last = CarbonImmutable::parse($to)->startOfMonth();
        while ($cursor <= $last) {
            $key = $cursor->format('Y-m');
            $months[] = [
                'key' => $key, 'label' => $cursor->locale('es')->translatedFormat('M Y'),
                'publications' => (int) ($publicationRows[$key] ?? 0),
                'future_publications' => (int) ($futurePublicationRows[$key] ?? 0), 'cgft' => (int) ($transferRows[$key] ?? 0),
                'from' => $cursor->toDateString(), 'to' => $cursor->endOfMonth()->toDateString(),
            ];
            $cursor = $cursor->addMonth();
        }

        return $months;
    }

    public function coverage(array $filters): array
    {
        $total = $this->manuals($filters)->count('manuals.id');
        $withVisits = $this->visits($filters)->distinct()->count('manuals.id');
        $withDatedVisits = $this->visits($filters)->whereNotNull('productivity_visit.entered_on')->distinct()->count('manuals.id');

        return [
            'total' => $total, 'with_visits' => $withVisits, 'with_dated_visits' => $withDatedVisits,
            'without_visits' => $total - $withVisits,
            'undated_visits' => $this->visits($filters)->whereNull('productivity_visit.entered_on')->count(),
            'undated_exits' => $this->visits($filters)->whereNotNull('productivity_visit.exit_event_id')->whereNull('productivity_visit.exited_on')->count(),
            'publications_without_date' => $this->manuals($filters)->where('manuals.is_published', true)->whereNull('manuals.published_on')->count(),
        ];
    }

    public function listing(array $filters, string $from, string $to, string $metric, ?string $phase): Builder
    {
        $columns = ['manuals.id as manual_id', 'manuals.manual_name', 'manuals.classification', 'productivity_type.code as type_code', 'productivity_map.name as map_name'];
        if ($metric === 'publications') {
            return $this->publications($filters, $from, $to)->select($columns)->addSelect('manuals.published_on as effective_on')->orderBy('manuals.published_on')->orderBy('manuals.id');
        }
        if ($metric === 'cgft') {
            return $this->cgftTransfers($filters, $from, $to)->select($columns)->selectRaw('MIN(productivity_visit.entered_on) as effective_on, COUNT(*) as matching_visits')
                ->groupBy('manuals.id', 'manuals.manual_name', 'manuals.classification', 'productivity_type.code', 'productivity_map.name')->orderBy('effective_on')->orderBy('manuals.id');
        }

        return $this->presence($filters, $from, $to)->where('productivity_phase.slug', $phase)->select($columns)
            ->selectRaw('MIN(productivity_visit.entered_on) as effective_on, COUNT(*) as matching_visits')
            ->groupBy('manuals.id', 'manuals.manual_name', 'manuals.classification', 'productivity_type.code', 'productivity_map.name')->orderBy('effective_on')->orderBy('manuals.id');
    }

    public function periods(array $filters): array
    {
        $year = (int) ($filters['year'] ?? today()->year);
        $from = $filters['from'] ?? sprintf('%04d-01-01', $year);
        $to = $filters['to'] ?? sprintf('%04d-12-31', $year);

        return [
            'from' => $from, 'to' => $to,
            'previous_from' => CarbonImmutable::parse($from)->subYearNoOverflow()->toDateString(),
            'previous_to' => CarbonImmutable::parse($to)->subYearNoOverflow()->toDateString(),
            'year' => $year,
        ];
    }
}
