<?php

declare(strict_types=1);

namespace App\Models;

use App\Audit\Concerns\Auditable;
use App\Enums\ServiceRequestStatus;
use App\Support\ReferenceGenerator;
use Database\Factories\ServiceRequestFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * @property ServiceRequestStatus $status
 * @property int|null $user_id
 * @property int|null $assigned_to
 * @property-read Service|null $service
 * @property-read User|null $user
 * @property-read Quote|null $quote
 */
#[Fillable([
    'reference', 'user_id', 'service_id', 'name', 'email', 'phone',
    'company', 'message', 'budget_hint', 'status', 'assigned_to',
])]
class ServiceRequest extends Model implements HasMedia
{
    /** @use HasFactory<ServiceRequestFactory> */
    use Auditable, HasFactory, InteractsWithMedia, SoftDeletes;

    /** @return array<int, string> */
    protected function auditAttributes(): array
    {
        return ['status', 'assigned_to'];
    }

    protected function auditGroup(): string
    {
        return 'devis';
    }

    protected function casts(): array
    {
        return [
            'status' => ServiceRequestStatus::class,
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (ServiceRequest $request): void {
            if (empty($request->reference)) {
                $request->reference = ReferenceGenerator::serviceRequest();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'reference';
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('attachments');
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<User, $this> */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /** @return BelongsTo<Service, $this> */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /** @return HasOne<Quote, $this> */
    public function quote(): HasOne
    {
        return $this->hasOne(Quote::class);
    }

    /** @return HasMany<RequestEvent, $this> */
    public function events(): HasMany
    {
        return $this->hasMany(RequestEvent::class)->latest();
    }

    /** @param  Builder<self>  $query */
    public function scopeForUser(Builder $query, User $user): void
    {
        $query->where('user_id', $user->id);
    }

    /**
     * Change le statut, journalise l'événement et retourne la demande.
     */
    public function transitionTo(ServiceRequestStatus $to, ?User $actor = null, ?string $note = null): self
    {
        $from = $this->status;

        if ($from === $to) {
            return $this;
        }

        $this->update(['status' => $to]);

        $this->events()->create([
            'from_status' => $from->value,
            'to_status' => $to->value,
            'note' => $note,
            'user_id' => $actor?->id,
        ]);

        return $this;
    }
}
