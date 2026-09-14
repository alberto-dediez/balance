<?php

declare(strict_types=1);

namespace App\Changelog\Domain\Model\ChangelogEntry;

enum ChangelogCategory: string
{
    case Feature = 'feature';

    case Improvement = 'improvement';

    case Fix = 'fix';

    case Security = 'security';
}
