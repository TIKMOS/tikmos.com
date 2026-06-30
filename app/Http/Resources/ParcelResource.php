<?php

namespace App\Http\Resources;

use App\Models\Parcel;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

/** @mixin Parcel */
class ParcelResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'reference' => $this->reference,
            'status' => [
                'value' => $this->status->value,
                'label' => $this->status->label(),
                'tone' => $this->status->tone(),
            ],
            'origin' => $this->origin->label(),
            'destination' => $this->destination->label(),
            'recipient' => $this->maskName($this->recipient_name),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'events' => $this->events->map(fn ($event): array => [
                'status' => $event->status->value,
                'label' => $event->status->label(),
                'comment' => $event->comment,
                'location' => $event->location,
                'happened_at' => $event->happened_at?->toIso8601String(),
            ])->values(),
        ];
    }

    /** Masque le nom du destinataire (vie privée) : « Aïcha K. ». */
    private function maskName(string $name): string
    {
        $parts = array_values(array_filter(explode(' ', trim($name))));

        if ($parts === []) {
            return '—';
        }

        $first = $parts[0];
        $initial = isset($parts[1]) ? ' '.Str::upper(Str::substr($parts[1], 0, 1)).'.' : '';

        return $first.$initial;
    }
}
