<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class GuestRequestRecoveryService
{
    public function recipientHash(string $email): string
    {
        return hash('sha256', Str::lower(trim($email)));
    }

    /** Crée le lien au moment de préparer l'email, jamais depuis une donnée client. */
    public function linkFor(ServiceRequest $request): string
    {
        return DB::transaction(function () use ($request): string {
            $fresh = ServiceRequest::query()->lockForUpdate()->findOrFail($request->id);
            if ($fresh->user_id !== null) {
                return route('app.requests.show', $fresh);
            }
            if (! filter_var($fresh->email, FILTER_VALIDATE_EMAIL)) {
                return route('request-recovery.renew');
            }

            $token = Str::random(64);
            $expiry = now()->addDay();
            DB::table('guest_request_claims')->insert([
                'service_request_id' => $fresh->id,
                'token_hash' => hash('sha256', $token),
                'recipient_hash' => $this->recipientHash($fresh->email),
                'expires_at' => $expiry,
                'used_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return URL::temporarySignedRoute('request-recovery.show', $expiry, ['token' => $token]);
        }, 3);
    }

    public function state(string $token, ?User $user): string
    {
        $claim = DB::table('guest_request_claims')->where('token_hash', hash('sha256', $token))->first();
        if (! $claim || Carbon::parse($claim->expires_at)->isPast()) {
            return 'invalid';
        }
        if ($claim->used_at !== null) {
            return 'used';
        }
        $request = ServiceRequest::find($claim->service_request_id);
        if (! $request || $request->user_id !== null || ! hash_equals($claim->recipient_hash, $this->recipientHash((string) $request->email))) {
            return 'invalid';
        }
        if (! $user) {
            return 'guest';
        }
        $user = $user->fresh();
        if (! $user || ! $user->isActive() || ! hash_equals($claim->recipient_hash, $this->recipientHash($user->email))) {
            return 'wrong-account';
        }

        return $user->hasVerifiedEmail() ? 'ready' : 'unverified';
    }

    public function claim(string $token, User $user): ServiceRequest
    {
        return DB::transaction(function () use ($token, $user): ServiceRequest {
            $entry = DB::table('guest_request_claims')->where('token_hash', hash('sha256', $token))->first();
            if (! $entry) {
                throw ValidationException::withMessages(['recovery' => 'Ce lien ne permet plus de rattacher une demande.']);
            }
            // Même verrou de demande pour deux liens différents : un seul rattachement possible.
            $request = ServiceRequest::query()->lockForUpdate()->find($entry->service_request_id);
            $account = User::query()->lockForUpdate()->find($user->id);
            $claim = DB::table('guest_request_claims')->where('id', $entry->id)->lockForUpdate()->first();
            if (! $request || ! $account || ! $claim || $claim->used_at !== null
                || Carbon::parse($claim->expires_at)->isPast() || $request->user_id !== null
                || ! $account->isActive() || ! $account->hasVerifiedEmail()
                || ! hash_equals($claim->recipient_hash, $this->recipientHash($account->email))
                || ! hash_equals($claim->recipient_hash, $this->recipientHash((string) $request->email))) {
                throw ValidationException::withMessages(['recovery' => 'Rattachement refusé. Vérifiez votre compte ou demandez un nouveau lien.']);
            }

            $updated = ServiceRequest::query()->whereKey($request->id)->whereNull('user_id')->update(['user_id' => $account->id]);
            if ($updated !== 1) {
                throw ValidationException::withMessages(['recovery' => 'Cette demande ne peut plus être rattachée.']);
            }
            DB::table('guest_request_claims')->where('service_request_id', $request->id)->whereNull('used_at')
                ->update(['used_at' => now(), 'updated_at' => now()]);
            $request->events()->create([
                'from_status' => $request->status->value,
                'to_status' => $request->status->value,
                'note' => 'Demande invitée rattachée après vérification de l’adresse email.',
                'user_id' => $account->id,
            ]);

            return $request->refresh();
        }, 3);
    }
}
