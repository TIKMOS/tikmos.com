<?php

declare(strict_types=1);

namespace App\Audit\Concerns;

use App\Audit\AuditContext;
use Spatie\Activitylog\Contracts\Activity as ActivityContract;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Traçabilité métier homogène : diffs avant/après, contexte (IP/UA/canal),
 * libellé lisible du sujet. À appliquer aux entités sensibles.
 *
 * Le modèle personnalise les champs surveillés et le journal en surchargeant :
 *   protected function auditAttributes(): array { return ['name', 'price', 'is_active']; }
 *   protected function auditGroup(): string { return 'boutique'; }
 */
trait Auditable
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly($this->auditAttributes())
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName($this->auditGroup());
    }

    /** Contexte + libellé lisible ajoutés à chaque activité. */
    public function tapActivity(ActivityContract $activity, string $eventName): void
    {
        if (! $activity instanceof Activity) {
            return;
        }

        $activity->properties = $activity->properties->merge([
            'context' => AuditContext::capture(),
            'subject_label' => $this->auditLabel(),
        ]);
    }

    /**
     * Champs surveillés (surchargé par le modèle). Par défaut : tout.
     *
     * @return array<int, string>
     */
    protected function auditAttributes(): array
    {
        return ['*'];
    }

    /** Nom du journal (surchargé par le modèle). */
    protected function auditGroup(): string
    {
        return 'metier';
    }

    /** Libellé humain du sujet (référence/nom/titre), pour l'affichage du journal. */
    public function auditLabel(): string
    {
        foreach (['reference', 'number', 'name', 'title'] as $key) {
            if (! empty($this->{$key})) {
                return (string) $this->{$key};
            }
        }

        return class_basename($this).' #'.$this->getKey();
    }
}
