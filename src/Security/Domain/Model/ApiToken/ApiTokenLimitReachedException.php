<?php

declare(strict_types=1);

namespace App\Security\Domain\Model\ApiToken;

class ApiTokenLimitReachedException extends \RuntimeException
{
    public function __construct()
    {
        parent::__construct(sprintf('Se ha alcanzado el máximo de %d tokens de API activos.', ApiToken::MAX_ACTIVE_PER_USER), 409);
    }
}
