<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Quote;
use App\Models\ServiceRequest;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ReferenceGenerator
{
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
