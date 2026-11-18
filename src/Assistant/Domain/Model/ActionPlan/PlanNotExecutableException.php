<?php

declare(strict_types=1);

namespace App\Assistant\Domain\Model\ActionPlan;

final class PlanNotExecutableException extends \DomainException
{
    public static function notProposed(): self
    {
        return new self('El plan ya no es el último propuesto');
    }

    public static function expired(): self
    {
        return new self('El plan ha caducado');
    }

    public static function notOwner(): self
    {
        return new self('El plan es de otro usuario');
    }

    public static function notApproved(): self
    {
        return new self('El plan no está aprobado');
    }

    public static function notExecuting(): self
    {
        return new self('El plan no se está ejecutando');
    }

    public static function notNextStep(int $position): self
    {
        return new self(sprintf('El paso %d no es el siguiente pendiente', $position));
    }
}
