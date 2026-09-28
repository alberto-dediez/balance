<?php

declare(strict_types=1);

namespace App\Materiality\Domain\Model\MaterialityTopic;

enum MaterialityScope: string
{
    case Scope1 = 'scope_1';

    case Scope2 = 'scope_2';

    case Scope3 = 'scope_3';
}
