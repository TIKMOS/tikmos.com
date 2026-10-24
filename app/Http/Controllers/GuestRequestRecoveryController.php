<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\ServiceRequest;
use App\Models\User;
use App\Notifications\GuestRequestEmailVerification;
use App\Notifications\GuestRequestRecoveryLink;
use App\Services\GuestRequestRecoveryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;

class GuestRequestRecoveryController extends Controller
{
    public function show(Request $request, string $token, GuestRequestRecoveryService $recovery): Response
    {
        $state = $request->hasValidSignature() ? $recovery->state($token, $request->user()) : 'invalid';
        if ($state === 'guest') {
            $request->session()->put('url.intended', $request->fullUrl());
        }

        return response()->view('request-recovery.show', ['state' => $state], $state === 'invalid' ? 403 : 200);
    }

    public function store(Request $request, string $token, GuestRequestRecoveryService $recovery): Response|RedirectResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);
        $state = $request->hasValidSignature() ? $recovery->state($token, $user) : 'invalid';
        if ($state === 'unverified' && $request->input('intent') === 'verify') {
            $key = 'guest-request-verification:'.$user->id;
            if (RateLimiter::attempt($key, 1, fn () => $user->notify(new GuestRequestEmailVerification($token)), 60) !== false) {
                return $this->backWithStatus($request, 'Un lien de vérification a été envoyé à l’adresse de votre compte.');
            }

            return $this->backWithStatus($request, 'Veuillez patienter une minute avant de demander un nouvel email.');
        }
        if ($state !== 'ready') {
            return response()->view('request-recovery.show', ['state' => $state], 403);
        }

        $claimed = $recovery->claim($token, $user);

        return new RedirectResponse(route('app.requests.show', $claimed));
    }

    public function verify(Request $request, string $id, string $hash, string $token): Response|RedirectResponse
    {
        if (! $request->hasValidSignature()) {
            return response()->view('request-recovery.show', ['state' => 'invalid'], 403);
        }
        $verified = DB::transaction(function () use ($request, $id, $hash): bool {
            $user = User::query()->lockForUpdate()->find($request->user()?->id);
            if (! $user || ! $user->isActive() || (string) $user->id !== $id
                || ! hash_equals(sha1($user->getEmailForVerification()), $hash)) {
                return false;
            }
            if (! $user->hasVerifiedEmail()) {
                $user->markEmailAsVerified();
            }

            return true;
        }, 3);
        if (! $verified) {
            return response()->view('request-recovery.show', ['state' => 'wrong-account'], 403);
        }

        // Une nouvelle signature ne prolonge pas l'échéance du jeton stockée en base.
        $request->session()->flash('status', 'Adresse vérifiée. Vous pouvez maintenant rattacher votre demande.');

        return new RedirectResponse(URL::temporarySignedRoute('request-recovery.show', now()->addDay(), ['token' => $token]));
    }

    private function backWithStatus(Request $request, string $message): RedirectResponse
    {
        $request->session()->flash('status', $message);

        return new RedirectResponse(URL::previous());
    }

    public function renew(): Response
    {
        return response()->view('request-recovery.renew');
    }

    public function sendRenewal(Request $request, GuestRequestRecoveryService $recovery): RedirectResponse
    {
        $data = $request->validate([
            'reference' => ['required', 'string', 'max:80'],
            'email' => ['required', 'email', 'max:255'],
        ]);
        $key = 'guest-request-renewal:'.hash('sha256', trim($data['reference']).$recovery->recipientHash($data['email']));
        RateLimiter::attempt($key, 1, function () use ($data, $recovery): void {
            $entry = ServiceRequest::where('reference', trim($data['reference']))->whereNull('user_id')->first();
            if ($entry && filter_var($entry->email, FILTER_VALIDATE_EMAIL)
                && hash_equals($recovery->recipientHash($entry->email), $recovery->recipientHash($data['email']))) {
                Notification::route('mail', $entry->email)->notify(new GuestRequestRecoveryLink($entry));
            }
        }, 60);

        return $this->backWithStatus($request, 'Si ces informations correspondent à une demande récupérable, un lien sera envoyé à son adresse email. Consultez aussi vos courriers indésirables.');
    }
}
