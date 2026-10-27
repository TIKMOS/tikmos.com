<?php

declare(strict_types=1);

namespace App\Enums;

enum DeliveryPricing: string
{
    case Unknown = 'unknown';
    case Free = 'free';
    case Fixed = 'fixed';
}
