<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\UI\Web\Navigation;

interface AccountNodeProvider
{
    /** @return list<Node> */
    public function nodes(): array;
}
