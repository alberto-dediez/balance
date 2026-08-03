<?php

declare(strict_types=1);

namespace App\Balance\Domain\Model\Project;

enum ProjectStatus: string
{
    case Pending = 'pending';

    case InProgress = 'in_progress';

    case Executed = 'executed';
}
