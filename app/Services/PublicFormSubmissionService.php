<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\Publication;
use App\Enums\ServiceRequestStatus;
use App\Models\Lead;
use App\Models\Service;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Notifications\ServiceRequestReceived;
use App\Notifications\ServiceRequestSubmitted;
use App\Settings\GeneralSettings;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use LogicException;

class PublicFormSubmissionService
{
    /** @param array<string, mixed> $data */
    public function quote(string $token, array $data, ?int $serviceId): ServiceRequest
    {
        $result = $this->once($token, 'quote', $data, function () use ($data, $serviceId): ServiceRequest {
            if ($serviceId !== null) {
                abort_unless(Service::whereKey($serviceId)->where('status', Publication::Published)->exists(), 404);
            }
            $request = ServiceRequest::create([
                'user_id' => Auth::id(), 'service_id' => $serviceId,
                'name' => $data['name'], 'email' => $data['email'] ?: null,
                'phone' => $data['phone'] ?: null, 'company' => $data['company'] ?: null,
                'message' => $data['message'], 'budget_hint' => $data['budget_hint'] ?: null,
                'status' => ServiceRequestStatus::Sent,
            ]);
            $request->events()->create([
                'from_status' => null, 'to_status' => ServiceRequestStatus::Sent->value,
                'note' => 'Demande créée depuis le formulaire public de devis.', 'user_id' => Auth::id(),
            ]);

            return $request;
        });
        if (! $result instanceof ServiceRequest) {
            throw new LogicException('Unexpected quote submission result.');
        }
        if ($result->wasRecentlyCreated) {
            // Après la transaction : aucun accusé pour un rejeu déjà enregistré.
            $teamEmail = app(GeneralSettings::class)->emails['contact'] ?? null;
            if ($teamEmail) {
                Notification::route('mail', $teamEmail)->notify(new ServiceRequestReceived($result));
            }
            if ($result->email) {
                Notification::route('mail', $result->email)->notify(new ServiceRequestSubmitted($result));
            }
        }

        return $result;
    }

    /** @param array<string, mixed> $data */
    public function contact(string $token, array $data): Lead
    {
        $result = $this->once($token, 'contact', $data, fn (): Lead => Lead::create([
            'name' => $data['name'], 'email' => $data['email'] ?: null,
            'phone' => $data['phone'] ?: null, 'message' => '['.$data['subject'].'] '.$data['message'],
            'source' => 'site',
        ]));
        if (! $result instanceof Lead) {
            throw new LogicException('Unexpected contact submission result.');
        }

        return $result;
    }

    /**
     * Réserve le reçu et écrit le résultat dans la même transaction.
     *
     * @param  array<string, mixed>  $data
     * @param  Closure(): (ServiceRequest|Lead)  $create
     */
    private function once(string $token, string $kind, array $data, Closure $create): Model
    {
        abort_unless(Str::isUuid($token), 422);
        if (Auth::check()) {
            $user = User::find(Auth::id());
            abort_unless($user && $user->isActive(), 403);
        }
        $key = hash('sha256', $token);
        $actor = hash_hmac('sha256', Auth::check() ? 'account:'.Auth::id() : 'session:'.session()->getId(), (string) config('app.key'));

        try {
            return DB::transaction(function () use ($key, $actor, $kind, $data, $create): Model {
                if ($existing = $this->existing($key, $actor, $kind)) {
                    return $existing;
                }
                DB::table('public_form_submissions')->insert([
                    'key' => $key, 'actor_hash' => $actor, 'kind' => $kind,
                    'created_at' => now(), 'updated_at' => now(),
                ]);
                app(PublicFormSubmissionLimiter::class)->ensureAllowed(request(), (string) $data['email'], (string) $data['phone']);
                $result = $create();
                DB::table('public_form_submissions')->where('key', $key)->update([
                    $kind === 'quote' ? 'service_request_id' : 'lead_id' => $result->getKey(),
                ]);

                return $result;
            }, 3);
        } catch (UniqueConstraintViolationException $exception) {
            // La réservation concurrente gagnante est déjà commitée ; relire hors rollback.
            return $this->existing($key, $actor, $kind) ?? throw $exception;
        }
    }

    private function existing(string $key, string $actor, string $kind): ServiceRequest|Lead|null
    {
        $receipt = DB::table('public_form_submissions')->where('key', $key)->first();
        if (! $receipt) {
            return null;
        }
        abort_unless(hash_equals($receipt->actor_hash, $actor) && $receipt->kind === $kind, 403);
        $result = $kind === 'quote' ? ServiceRequest::find($receipt->service_request_id) : Lead::find($receipt->lead_id);
        if (! $result) {
            throw ValidationException::withMessages(['submission' => 'Cet envoi a déjà été traité. Contactez notre équipe si nécessaire. Votre texte est conservé.']);
        }

        return $result;
    }
}
