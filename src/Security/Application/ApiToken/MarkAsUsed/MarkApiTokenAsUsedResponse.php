<?php

declare(strict_types=1);

namespace App\Security\Application\ApiToken\MarkAsUsed;

class MarkApiTokenAsUsedResponse
{
    public function __construct(public bool $marked) {}
}
