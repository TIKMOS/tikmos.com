<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\QuoteStatus;
use App\Enums\ServiceRequestStatus;
use App\Models\Quote;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Support\ReferenceGenerator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class QuoteService
{
    public function decide(ServiceRequest $request, User $client, QuoteStatus $decision): void
    {
        abort_unless(in_array($decision, [QuoteStatus::Accepted, QuoteStatus::Refused], true), 422);

        DB::transaction(function () use ($request, $client, $decision): void {
            $request = ServiceRequest::query()->lockForUpdate()->findOrFail($request->id);
            abort_unless($request->user_id === $client->id, 403);
            $quote = $request->quote()->lockForUpdate()->first();
            abort_if($quote === null || $quote->status !== QuoteStatus::Sent, 422);

            // Relecture sous verrou : une page ouverte avant expiration n'autorise pas la décision.
            if ($quote->isExpired()) {
                throw ValidationException::withMessages([
                    'quote' => 'Ce devis a expiré. Demandez son renouvellement avant de prendre une décision.',
                ]);
            }

            $quote->update(['status' => $decision, 'decided_at' => now()]);
            $request->transitionTo(
                $decision === QuoteStatus::Accepted ? ServiceRequestStatus::Accepted : ServiceRequestStatus::Refused,
                $client,
                $decision === QuoteStatus::Accepted ? 'Devis accepté par le client.' : 'Devis refusé par le client.',
            );
        }, attempts: 3);
    }

    /** @param array{amount: int, currency: string, validUntil: string, quoteBody: string} $input */
    public function send(ServiceRequest $request, User $staff, array $input): Quote
    {
        abort_unless($staff->can('quotes.send'), 403);

        return DB::transaction(function () use ($request, $staff, $input): Quote {
            $request = ServiceRequest::query()->lockForUpdate()->findOrFail($request->id);
            $quote = $request->quote()->lockForUpdate()->first();
            if ($quote?->hasDecision()) {
                throw ValidationException::withMessages([
                    'quote' => 'Ce devis a déjà fait l’objet d’une décision. Son montant et sa décision sont conservés.',
                ]);
            }

            $data = Validator::make($input, [
                'amount' => ['required', 'integer', 'min:0'],
                'currency' => ['required', 'string', 'max:8'],
                'validUntil' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:'.now(config('app.timezone'))->toDateString()],
                'quoteBody' => ['nullable', 'string', 'max:5000'],
            ], [
                'validUntil.date_format' => 'Indiquez une date de validité au format jour/mois/année dans le sélecteur.',
                'validUntil.after_or_equal' => 'La date de validité doit être aujourd’hui ou une date future.',
            ])->validate();

            $quote ??= new Quote([
                'service_request_id' => $request->id,
                'reference' => ReferenceGenerator::quote(),
            ]);
            $quote->fill([
                'amount' => $data['amount'],
                'currency' => $data['currency'],
                'valid_until' => $data['validUntil'] ?: null,
                'body' => $data['quoteBody'] ?: null,
                'status' => QuoteStatus::Sent,
                'sent_at' => now(),
            ])->save();

            $request->transitionTo(ServiceRequestStatus::Quoted, $staff, 'Devis '.$quote->reference.' envoyé.');

            return $quote;
        }, attempts: 3);
    }
}
