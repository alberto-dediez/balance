<?php

declare(strict_types=1);

namespace App\Balance\Infrastructure\LegacyImport;

enum OrganizationTarget: string
{
    case Skip = 'skip';

    case Create = 'create';

    case Reuse = 'reuse';
}
