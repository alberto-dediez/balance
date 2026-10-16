<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\EconomicSector;

final class EconomicSectorCodeAlreadyExistsException extends \DomainException
{
    public function __construct(private readonly string $classificationCode)
    {
        parent::__construct(sprintf('An economic sector with code "%s" already exists.', $classificationCode));
    }

    public function code(): string
    {
        return $this->classificationCode;
    }
}
