<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\Contest;

class ContestHostAlreadyInUseException extends \RuntimeException
{
    public function __construct(private readonly string $hostName)
    {
        parent::__construct(
            sprintf('La calculadora «%s» ya tiene otro concurso publicado.', $hostName),
            409,
        );
    }

    public function hostName(): string
    {
        return $this->hostName;
    }
}
