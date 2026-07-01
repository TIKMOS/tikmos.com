<?php

declare(strict_types=1);

namespace App\Audit;

use App\Models\LoginLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Spatie\Activitylog\Models\Activity;

/**
 * Agrège le journal Spatie (activity_log) et les connexions (login_logs) en une
 * timeline normalisée : { at, actor, action, subject, subject_type, context, changes, source }.
 */
class ActivityFeed
{
    /**
     * Requête filtrable sur le journal d'activité (pour la liste admin paginée).
     *
     * @param  array{causer_id?: int|null, log_name?: string|null, event?: string|null, subject_type?: string|null, from?: string|null, to?: string|null}  $filters
     * @return Builder<Activity>
     */
    public function query(array $filters = []): Builder
    {
        return Activity::query()
            ->with(['causer', 'subject'])
            ->when($filters['causer_id'] ?? null, fn (Builder $q, $id) => $q->where('causer_id', $id))
            ->when($filters['log_name'] ?? null, fn (Builder $q, $name) => $q->where('log_name', $name))
            ->when($filters['event'] ?? null, fn (Builder $q, $event) => $q->where('event', $event))
            ->when($filters['subject_type'] ?? null, fn (Builder $q, $type) => $q->where('subject_type', $type))
            ->when($filters['from'] ?? null, fn (Builder $q, $from) => $q->whereDate('created_at', '>=', $from))
            ->when($filters['to'] ?? null, fn (Builder $q, $to) => $q->whereDate('created_at', '<=', $to))
            ->latest();
    }

    /** @return array<string, mixed> */
    public function normalize(Activity $activity): array
    {
        /** @var array<string, mixed> $props */
        $props = $activity->properties->toArray();

        return [
            'at' => $activity->created_at,
            'actor' => $activity->causer instanceof User ? $activity->causer->name : 'Système',
            'action' => (string) ($activity->event ?? $activity->description),
            'subject' => $props['subject_label'] ?? (class_basename((string) $activity->subject_type).' #'.$activity->subject_id),
            'subject_type' => class_basename((string) $activity->subject_type),
            'context' => $props['context'] ?? [],
            'changes' => [
                'old' => $props['old'] ?? [],
                'new' => $props['attributes'] ?? [],
            ],
            'source' => 'activity',
        ];
    }

    /**
     * Timeline unifiée d'un acteur (activités qu'il a causées + ses connexions).
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function timelineFor(User $actor, int $limit = 50): Collection
    {
        $activities = $this->query(['causer_id' => $actor->id])
            ->limit($limit)->get()
            ->map(fn (Activity $a) => $this->normalize($a));

        $logins = LoginLog::where('user_id', $actor->id)
            ->latest('logged_in_at')->limit($limit)->get()
            ->map(fn (LoginLog $l): array => [
                'at' => $l->logged_in_at,
                'actor' => $actor->name,
                'action' => $l->action, // login | logout | failed
                'subject' => 'Connexion',
                'subject_type' => 'Auth',
                'context' => array_filter(['ip' => $l->ip_address, 'ua' => $l->browser.' · '.$l->os, 'channel' => 'auth']),
                'changes' => ['old' => [], 'new' => []],
                'source' => 'login',
            ]);

        return $activities->concat($logins)
            ->sortByDesc('at')
            ->take($limit)
            ->values();
    }

    /**
     * Options de filtre pour l'UI (types de sujets et journaux distincts présents).
     *
     * @return array{log_names: array<int, string>, subject_types: array<string, string>}
     */
    public function facets(): array
    {
        return [
            'log_names' => Activity::query()->distinct()->pluck('log_name')->filter()->values()->all(),
            'subject_types' => Activity::query()->distinct()->pluck('subject_type')->filter()
                ->mapWithKeys(fn ($t) => [$t => class_basename((string) $t)])->all(),
        ];
    }
}
