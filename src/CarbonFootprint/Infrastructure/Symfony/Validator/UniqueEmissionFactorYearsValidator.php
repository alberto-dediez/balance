<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Symfony\Validator;

use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class UniqueEmissionFactorYearsValidator extends ConstraintValidator
{
    private PropertyAccessorInterface $propertyAccessor;

    public function __construct(?PropertyAccessorInterface $propertyAccessor = null)
    {
        $this->propertyAccessor = $propertyAccessor ?? PropertyAccess::createPropertyAccessor();
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof UniqueEmissionFactorYears) {
            throw new UnexpectedTypeException($constraint, UniqueEmissionFactorYears::class);
        }

        if (null === $value) {
            return;
        }

        if (!is_iterable($value)) {
            throw new UnexpectedValueException($value, 'iterable');
        }

        $seenYears = [];

        foreach ($value as $index => $row) {
            $year = $this->propertyAccessor->getValue($row, $constraint->yearPath);

            if (null === $year) {
                continue;
            }

            if (in_array($year, $seenYears, true)) {
                $this->context->buildViolation($constraint->message)
                    ->atPath(sprintf('[%s].%s', $index, $constraint->yearPath))
                    ->setParameter('{{ year }}', (string) $year)
                    ->addViolation();

                continue;
            }

            $seenYears[] = $year;
        }
    }
}
