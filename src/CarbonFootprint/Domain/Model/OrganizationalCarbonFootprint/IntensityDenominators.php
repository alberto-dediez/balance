<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint;

final readonly class IntensityDenominators
{
    public function __construct(
        private ?int $occupiedRooms = null,
        private ?int $stays = null,
        private ?string $builtAreaM2 = null,
        private ?int $coversServed = null,
        private ?string $averageHeadcount = null,
        private ?string $revenueEur = null,
        private ?int $customersServed = null,
    ) {
        foreach ($this->values() as $name => $value) {
            if (null !== $value && (float) $value <= 0.0) {
                throw new \InvalidArgumentException(sprintf('Intensity denominator "%s" must be greater than zero', $name));
            }
        }
    }

    public static function empty(): self
    {
        return new self();
    }

    /** @param list<self> $denominators */
    public static function sum(array $denominators): self
    {
        return new self(
            occupiedRooms: self::sumIntegers(array_map(static fn(self $d): ?int => $d->occupiedRooms, $denominators)),
            stays: self::sumIntegers(array_map(static fn(self $d): ?int => $d->stays, $denominators)),
            builtAreaM2: self::sumDecimals(array_map(static fn(self $d): ?string => $d->builtAreaM2, $denominators)),
            coversServed: self::sumIntegers(array_map(static fn(self $d): ?int => $d->coversServed, $denominators)),
            averageHeadcount: self::sumDecimals(array_map(static fn(self $d): ?string => $d->averageHeadcount, $denominators)),
            revenueEur: self::sumDecimals(array_map(static fn(self $d): ?string => $d->revenueEur, $denominators)),
            customersServed: self::sumIntegers(array_map(static fn(self $d): ?int => $d->customersServed, $denominators)),
        );
    }

    public function occupiedRooms(): ?int
    {
        return $this->occupiedRooms;
    }

    public function stays(): ?int
    {
        return $this->stays;
    }

    public function builtAreaM2(): ?string
    {
        return $this->builtAreaM2;
    }

    public function coversServed(): ?int
    {
        return $this->coversServed;
    }

    public function averageHeadcount(): ?string
    {
        return $this->averageHeadcount;
    }

    public function revenueEur(): ?string
    {
        return $this->revenueEur;
    }

    public function customersServed(): ?int
    {
        return $this->customersServed;
    }

    public function isEmpty(): bool
    {
        foreach ($this->values() as $value) {
            if (null !== $value) {
                return false;
            }
        }

        return true;
    }

    public function denominatorFor(IntensityIndicator $indicator): ?float
    {
        $value = match ($indicator) {
            IntensityIndicator::PerOccupiedRoom => $this->occupiedRooms,
            IntensityIndicator::PerStay => $this->stays,
            IntensityIndicator::PerSquareMeter => $this->builtAreaM2,
            IntensityIndicator::PerCover => $this->coversServed,
            IntensityIndicator::PerEmployee => $this->averageHeadcount,
            IntensityIndicator::PerMillionEuroRevenue => $this->revenueEur,
            IntensityIndicator::PerCustomer => $this->customersServed,
        };

        if (null === $value) {
            return null;
        }

        return IntensityIndicator::PerMillionEuroRevenue === $indicator ? (float) $value / 1_000_000 : (float) $value;
    }

    public function equals(self $other): bool
    {
        $otherValues = $other->values();

        foreach ($this->values() as $name => $value) {
            $otherValue = $otherValues[$name];

            if (null === $value || null === $otherValue) {
                if ($value !== $otherValue) {
                    return false;
                }

                continue;
            }

            if (abs((float) $value - (float) $otherValue) > 0.000001) {
                return false;
            }
        }

        return true;
    }

    /** @return array<string, int|string|null> */
    private function values(): array
    {
        return [
            'occupiedRooms' => $this->occupiedRooms,
            'stays' => $this->stays,
            'builtAreaM2' => $this->builtAreaM2,
            'coversServed' => $this->coversServed,
            'averageHeadcount' => $this->averageHeadcount,
            'revenueEur' => $this->revenueEur,
            'customersServed' => $this->customersServed,
        ];
    }

    /** @param list<?int> $values */
    private static function sumIntegers(array $values): ?int
    {
        $present = array_values(array_filter($values, static fn(?int $value): bool => null !== $value));

        return [] === $present ? null : array_sum($present);
    }

    /** @param list<?string> $values */
    private static function sumDecimals(array $values): ?string
    {
        $present = array_values(array_filter($values, static fn(?string $value): bool => null !== $value));

        if ([] === $present) {
            return null;
        }

        return number_format(array_sum(array_map(static fn(string $value): float => (float) $value, $present)), 2, '.', '');
    }
}
