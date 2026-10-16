<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\Transport;

enum TransportMode: string
{
    case Road = 'road';
    case Rail = 'rail';
    case Sea  = 'sea';
    case Air  = 'air';
}
