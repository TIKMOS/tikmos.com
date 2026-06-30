<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ParcelStatus;
use App\Models\Parcel;
use App\Models\ReviewRequest;
use App\Notifications\ReviewRequested;
use App\Settings\ReviewsSettings;
use Illuminate\Support\Str;

/**
 * Sollicite des avis pour les colis livrés depuis « delay_days », sans avis ni
 * sollicitation récente (anti-spam « cooldown_days »).
 */
class ReviewSolicitationService
{
    public function solicitForDeliveredParcels(): int
    {
        $settings = app(ReviewsSettings::class);
        $delayCutoff = now()->subDays($settings->delay_days);
        $cooldownCutoff = now()->subDays($settings->cooldown_days);

        $parcels = Parcel::query()
            ->where('status', ParcelStatus::Delivered->value)
            ->whereNotNull('sender_id')
            ->whereNotNull('delivered_at')
            ->where('delivered_at', '<=', $delayCutoff)
            ->with('sender')
            ->get();

        $count = 0;

        foreach ($parcels as $parcel) {
            if ($parcel->sender === null) {
                continue;
            }

            // Déjà un avis sur ce colis ?
            if ($parcel->reviews()->exists()) {
                continue;
            }

            // Déjà une sollicitation pour ce colis ?
            $alreadyRequested = ReviewRequest::query()
                ->where('subject_type', $parcel->getMorphClass())
                ->where('subject_id', $parcel->id)
                ->exists();
            if ($alreadyRequested) {
                continue;
            }

            // Cooldown : une sollicitation récente pour cet utilisateur ?
            $recent = ReviewRequest::query()
                ->where('user_id', $parcel->sender_id)
                ->where('created_at', '>=', $cooldownCutoff)
                ->exists();
            if ($recent) {
                continue;
            }

            $request = ReviewRequest::create([
                'user_id' => $parcel->sender_id,
                'subject_type' => $parcel->getMorphClass(),
                'subject_id' => $parcel->id,
                'token' => Str::random(40),
                'channel' => $parcel->sender->email ? 'email' : 'app',
                'sent_at' => now(),
                'expires_at' => now()->addDays(30),
            ]);

            $parcel->sender->notify(new ReviewRequested($parcel, $request));
            $count++;
        }

        return $count;
    }
}
