<?php

declare(strict_types=1);

namespace App\DataExchange\Domain\Model\Exportation;

enum ExportationStatus: string
{
    case Started = 'Started';

    case InProgress = 'InProgress';

    case Completed = 'Completed';

    case Failed = 'Failed';
}
