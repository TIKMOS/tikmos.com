<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Parcel;
use App\Models\Quote;
use App\Models\ServiceRequest;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ReferenceGenerator
{
    /** Génère la prochaine référence colis (SZ-AAAA-NNNNN), séquence par année, verrouillée. */
    public static function next(): string
    {
        return DB::transaction(function (): string {
            $prefix = 'SZ-'.now()->year.'-';

            $last = Parcel::withTrashed()
                ->where('reference', 'like', $prefix.'%')
                ->lockForUpdate()
                ->orderByDesc('reference')
                ->value('reference');

            $sequence = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

            return $prefix.str_pad((string) $sequence, 5, '0', STR_PAD_LEFT);
        });
    }

    public static function qrToken(): string
    {
        return Str::random(40);
    }

    /** Référence unique de demande de devis : TKM-DEV-XXXXXX. */
    public static function serviceRequest(): string
    {
        return self::uniqueToken('TKM-DEV-', ServiceRequest::class);
    }

    /** Référence unique de devis : TKM-Q-XXXXXX. */
    public static function quote(): string
    {
        return self::uniqueToken('TKM-Q-', Quote::class);
    }

    /**
     * @param  class-string<Model>  $model
     */
    private static function uniqueToken(string $prefix, string $model): string
    {
        do {
            $reference = $prefix.Str::upper(Str::random(6));
        } while ($model::query()->where('reference', $reference)->exists());

        return $reference;
    }
}
