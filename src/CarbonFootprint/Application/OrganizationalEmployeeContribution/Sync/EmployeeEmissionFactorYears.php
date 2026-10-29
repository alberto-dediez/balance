<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\OrganizationalEmployeeContribution\Sync;

use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\Mobility\Mobility;
use App\CarbonFootprint\Domain\Model\OvernightStay\OvernightStay;

final class EmployeeEmissionFactorYears
{
    /** @var array<string, int|false> */
    private array $years = [];

    /** @var array<string, string> */
    private array $skipped = [];

    /** @var array<string, int> */
    private array $fallbackYears = [];

    public function __construct(
        private readonly int $calculationYear,
        private readonly ?EmissionFactorGroupId $groupId,
    ) {}

    public function resolveMobility(string $key, ?Mobility $mobility): bool
    {
        if (!array_key_exists($key, $this->years)) {
            $this->years[$key] = $this->resolve(
                $key,
                $mobility?->name(),
                null !== $mobility && null !== $this->groupId ? $mobility->emissionFactors()->nearestYearForGroup($this->calculationYear, $this->groupId) : null,
            );
        }

        return false !== $this->years[$key];
    }

    public function resolveOvernightStay(string $key, OvernightStay $overnightStay): bool
    {
        if (!array_key_exists($key, $this->years)) {
            $this->years[$key] = $this->resolve(
                $key,
                $overnightStay->name(),
                null !== $this->groupId ? $overnightStay->emissionFactors()->nearestYearForGroup($this->calculationYear, $this->groupId) : null,
            );
        }

        return false !== $this->years[$key];
    }

    public function yearFor(string $key): ?int
    {
        $year = $this->years[$key] ?? false;

        return false === $year || $year === $this->calculationYear ? null : $year;
    }

    /** @return list<string> */
    public function skipped(): array
    {
        return array_values($this->skipped);
    }

    /** @return array<string, int> */
    public function fallbackYears(): array
    {
        return $this->fallbackYears;
    }

    private function resolve(string $key, ?string $name, ?int $nearestYear): int|false
    {
        if (null === $this->groupId) {
            return $this->calculationYear;
        }

        if (null === $name || null === $nearestYear) {
            $this->skipped[$key] = $name ?? $key;

            return false;
        }

        if ($nearestYear !== $this->calculationYear) {
            $this->fallbackYears[$name] = $nearestYear;
        }

        return $nearestYear;
    }
}
