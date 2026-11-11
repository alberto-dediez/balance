<?php

declare(strict_types=1);

namespace App\Security\Application\ApiToken\List;

use App\Security\Domain\Model\ApiToken\ApiToken;

class ListApiTokensResponse
{
    /** @param array<ApiToken> $apiTokens */
    public function __construct(public array $apiTokens = [], public int $totalResults = 0) {}
}
