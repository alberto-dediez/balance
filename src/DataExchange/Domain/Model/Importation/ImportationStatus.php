<?php

declare(strict_types=1);

namespace App\DataExchange\Domain\Model\Importation;

enum ImportationStatus: string
{
    case Started = 'Started';

    case InProgress = 'InProgress';

    case Executed = 'Executed';
}
