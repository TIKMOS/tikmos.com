<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ParcelStatus;
use App\Enums\RequestStatus;
use App\Models\Parcel;
use App\Models\ParcelEvent;
use App\Models\ParcelRequest;
use App\Models\User;
use App\Notifications\ParcelReady;
use App\Notifications\ParcelStatusChanged;
use App\Support\ReferenceGenerator;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ParcelService
{
    /**
     * Crée un colis (référence + QR auto) et son premier événement de suivi.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): Parcel
    {
        return DB::transaction(function () use ($attributes): Parcel {
            $attributes['reference'] ??= ReferenceGenerator::next();
            $attributes['qr_token'] ??= ReferenceGenerator::qrToken();
            $attributes['status'] ??= ParcelStatus::Registered->value;

            $parcel = Parcel::create($attributes);

            $parcel->events()->create([
                'status' => $parcel->status->value,
                'comment' => 'Colis enregistré',
                'location' => $parcel->origin->label(),
                'created_by' => $attributes['agent_id'] ?? null,
                'happened_at' => now(),
            ]);

            return $parcel;
        });
    }

    /**
     * Fait avancer le statut d'un colis (transition contrôlée) :
     * crée un événement, met à jour le colis, déclenche la notification (en file).
     */
    public function advanceTo(
        Parcel $parcel,
        ParcelStatus $to,
        ?User $by = null,
        ?string $comment = null,
        ?string $location = null,
    ): ParcelEvent {
        $from = $parcel->status;

        if ($from === $to) {
            throw new InvalidArgumentException('Le colis est déjà à ce statut.');
        }

        if (! $from->canTransitionTo($to)) {
            throw new InvalidArgumentException("Transition invalide : {$from->value} → {$to->value}.");
        }

        return DB::transaction(function () use ($parcel, $to, $by, $comment, $location): ParcelEvent {
            $event = $parcel->events()->create([
                'status' => $to->value,
                'comment' => $comment,
                'location' => $location,
                'created_by' => $by?->id,
                'happened_at' => now(),
            ]);

            $parcel->status = $to;

            if ($to === ParcelStatus::Delivered) {
                $parcel->delivered_at = now();
            }

            $parcel->save();

            $this->notifyClient($parcel, $to);

            return $event;
        });
    }

    /** Convertit une demande client en colis et marque la demande comme convertie. */
    public function convertRequest(ParcelRequest $request, ?User $agent = null): Parcel
    {
        return DB::transaction(function () use ($request, $agent): Parcel {
            $parcel = $this->create([
                'sender_id' => $request->user_id,
                'recipient_name' => $request->recipient_name ?: 'À préciser',
                'recipient_phone' => $request->recipient_phone,
                'type' => $request->type->value,
                'weight_kg' => $request->weight_estimate,
                'origin' => $request->origin->value,
                'destination' => $request->destination->value,
                'agent_id' => $agent?->id,
            ]);

            $request->update([
                'status' => RequestStatus::Converted->value,
                'converted_parcel_id' => $parcel->id,
            ]);

            return $parcel;
        });
    }

    private function notifyClient(Parcel $parcel, ParcelStatus $to): void
    {
        $client = $parcel->sender;

        if (! $client) {
            return; // colis « walk-in » sans compte : pas de notification interne
        }

        $client->notify($to === ParcelStatus::ReadyForPickup
            ? new ParcelReady($parcel)
            : new ParcelStatusChanged($parcel, $to));
    }
}
