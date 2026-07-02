<?php

declare(strict_types=1);

namespace App\EventOperations\Infrastructure\Symfony\Form;

use Symfony\Component\Validator\Context\ExecutionContextInterface;

trait EventDateRangeValidationTrait
{
    public static function validateDateRanges(
        ?\DateTimeImmutable $assemblyStartDate,
        ?\DateTimeImmutable $assemblyEndDate,
        ?\DateTimeImmutable $celebrationStartDate,
        ?\DateTimeImmutable $celebrationEndDate,
        ?\DateTimeImmutable $breakdownStartDate,
        ?\DateTimeImmutable $breakdownEndDate,
        ExecutionContextInterface $context,
    ): void {
        self::validateRange($assemblyStartDate, $assemblyEndDate, 'assemblyEndDate', 'El fin del montaje no puede ser anterior a su inicio.', $context);
        self::validateRange($celebrationStartDate, $celebrationEndDate, 'celebrationEndDate', 'El fin de la celebración no puede ser anterior a su inicio.', $context);
        self::validateRange($breakdownStartDate, $breakdownEndDate, 'breakdownEndDate', 'El fin del desmontaje no puede ser anterior a su inicio.', $context);
    }

    private static function validateRange(
        ?\DateTimeImmutable $start,
        ?\DateTimeImmutable $end,
        string $path,
        string $message,
        ExecutionContextInterface $context,
    ): void {
        if (null === $start || null === $end) {
            return;
        }

        if ($start > $end) {
            $context->buildViolation($message)
                ->atPath($path)
                ->addViolation()
            ;
        }
    }
}
