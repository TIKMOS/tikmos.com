<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ParcelResource;
use App\Models\Parcel;

class TrackParcelController extends Controller
{
    /** Suivi public d'un colis par référence (aucune donnée sensible exposée). */
    public function __invoke(Parcel $parcel): ParcelResource
    {
        $parcel->load('events');

        return new ParcelResource($parcel);
    }
}
