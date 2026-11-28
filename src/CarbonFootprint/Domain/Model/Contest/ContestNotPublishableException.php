<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\Contest;

class ContestNotPublishableException extends \RuntimeException
{
    public function __construct(string $message)
    {
        parent::__construct($message, 409);
    }
}
