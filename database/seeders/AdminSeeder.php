<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use RuntimeException;
use Spatie\Permission\Models\Role as PermissionRole;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $name = config('admin.name');
        $email = config('admin.email');
        $password = config('admin.password');

        if (! is_string($name) || ! is_string($email) || ! is_string($password)) {
            throw new RuntimeException('Provisionnement explicite : renseignez TIKMOS_ADMIN_NAME, TIKMOS_ADMIN_EMAIL et TIKMOS_ADMIN_PASSWORD. Aucun compte créé.');
        }
        $name = trim($name);
        $email = Str::lower(trim($email));
        $validator = Validator::make(['name' => $name, 'email' => $email, 'password' => $password], [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', Password::min(16)->mixedCase()->numbers()->symbols()],
        ]);
        if (strlen($password) > 72 || str_contains($password, "\0") || $validator->fails()) {
            // Ne pas lever ValidationException : elle conserverait les données du validateur.
            throw new RuntimeException('Configuration administrateur invalide : nom requis (255 caractères max.), email valide et mot de passe de 16 caractères minimum, 72 octets maximum, avec majuscule, minuscule, chiffre et symbole, sans octet nul. Aucun compte modifié.');
        }

        try {
            $created = DB::transaction(function () use ($name, $email, $password): bool {
                // Verrou commun aux provisionnements initiaux, y compris pour deux emails différents.
                $role = PermissionRole::where('name', Role::SuperAdmin->value)->where('guard_name', 'web')->lockForUpdate()->first();
                if (! $role) {
                    throw new RuntimeException('Rôles absents : exécutez d’abord php artisan db:seed --class=RolePermissionSeeder. Aucun compte créé.');
                }

                $existing = User::withTrashed()->whereRaw('LOWER(email) = ?', [$email])->lockForUpdate()->get();
                if ($existing->count() > 1) {
                    throw new RuntimeException('Adresse ambiguë : plusieurs comptes correspondent. Vérification manuelle requise, aucun compte modifié.');
                }
                if ($account = $existing->first()) {
                    if ($account->trashed() || ! $account->hasRole(Role::SuperAdmin->value, 'web')) {
                        throw new RuntimeException('Adresse déjà utilisée par un compte non admissible au provisionnement initial. Aucune promotion ni réactivation automatique.');
                    }

                    return false;
                }
                if (User::withTrashed()->role(Role::SuperAdmin->value, 'web')->exists()) {
                    throw new RuntimeException('Un SuperAdmin existe déjà. Utilisez la gestion du personnel pour les comptes supplémentaires. Aucun compte créé.');
                }

                $admin = User::create([
                    'name' => $name,
                    'email' => $email,
                    'password' => Hash::make($password),
                    'status' => 'active',
                ]);
                $admin->assignRole($role);

                return true;
            }, 3);
        } catch (QueryException) {
            // Ne pas exposer la requête SQL, ses paramètres ou un hachage de mot de passe.
            throw new RuntimeException('Provisionnement annulé : vérifiez la connexion, le schéma et les conflits de comptes. Aucune modification partielle conservée.');
        }

        // Le mécanisme documenté passe par db:seed, qui fournit sa commande au seeder.
        $this->command->info($created
            ? 'Administrateur initial créé. Double authentification requise à la connexion.'
            : 'SuperAdmin déjà présent : compte, mot de passe, statut et rôles inchangés.');
    }
}
