<?php

declare(strict_types=1);

namespace App\Shared\Domain\Model\Integration;

enum IntegrationOutboxStatus: string
{
    case Pending = 'pending';
    case Sent = 'sent';
    case All = 'all';
}
