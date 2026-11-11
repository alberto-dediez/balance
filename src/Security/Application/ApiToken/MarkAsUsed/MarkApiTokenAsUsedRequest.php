<?php

declare(strict_types=1);

namespace App\Security\Application\ApiToken\MarkAsUsed;

use App\Security\Domain\Model\ApiToken\ApiTokenId;

class MarkApiTokenAsUsedRequest
{
    public function __construct(public ApiTokenId $apiTokenId) {}
}
