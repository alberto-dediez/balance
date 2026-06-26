<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Infrastructure\Symfony\Validator;

use App\CarbonFootprint\Application\CateringItem\Update\EmissionFactorData;
use App\CarbonFootprint\Infrastructure\Symfony\Validator\UniqueEmissionFactorYears;
use App\CarbonFootprint\Infrastructure\Symfony\Validator\UniqueEmissionFactorYearsValidator;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/** @extends ConstraintValidatorTestCase<UniqueEmissionFactorYearsValidator> */
class UniqueEmissionFactorYearsValidatorTest extends ConstraintValidatorTestCase
{
    public function testItIgnoresNullCollection(): void
    {
        $this->validator->validate(null, new UniqueEmissionFactorYears());

        $this->assertNoViolation();
    }

    public function testItIgnoresEmptyCollection(): void
    {
        $this->validator->validate([], new UniqueEmissionFactorYears());

        $this->assertNoViolation();
    }

    public function testItAllowsDistinctYears(): void
    {
        $this->validator->validate(
            [$this->rowForYear(2023), $this->rowForYear(2024)],
            new UniqueEmissionFactorYears(),
        );

        $this->assertNoViolation();
    }

    public function testItIgnoresRowsWithoutYear(): void
    {
        $this->validator->validate(
            [$this->rowForYear(null), $this->rowForYear(null)],
            new UniqueEmissionFactorYears(),
        );

        $this->assertNoViolation();
    }

    public function testItRaisesViolationOnTheDuplicatedYearRow(): void
    {
        $this->validator->validate(
            [$this->rowForYear(2023), $this->rowForYear(2023)],
            new UniqueEmissionFactorYears(),
        );

        $this->buildViolation('Ya existe un factor de emisión para el año {{ year }}')
            ->atPath('property.path[1].year')
            ->setParameter('{{ year }}', '2023')
            ->assertRaised();
    }

    public function testItThrowsOnUnexpectedConstraint(): void
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate([], new NotNull());
    }

    public function testItThrowsOnNonIterableValue(): void
    {
        $this->expectException(UnexpectedValueException::class);

        $this->validator->validate('not-iterable', new UniqueEmissionFactorYears());
    }

    protected function createValidator(): UniqueEmissionFactorYearsValidator
    {
        return new UniqueEmissionFactorYearsValidator();
    }

    private function rowForYear(?int $year): EmissionFactorData
    {
        return new EmissionFactorData(year: $year);
    }
}
