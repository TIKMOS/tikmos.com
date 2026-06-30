<?php

namespace App\Policies;

use App\Models\Parcel;
use App\Models\User;

class ParcelPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('colis.view');
    }

    public function view(User $user, Parcel $parcel): bool
    {
        return $parcel->sender_id === $user->id || $user->can('colis.view');
    }

    public function create(User $user): bool
    {
        return $user->can('colis.create');
    }

    public function update(User $user, Parcel $parcel): bool
    {
        return $user->can('colis.update');
    }

    public function advance(User $user, Parcel $parcel): bool
    {
        return $user->can('colis.advance');
    }

    public function delete(User $user, Parcel $parcel): bool
    {
        return $user->can('colis.delete');
    }
}
