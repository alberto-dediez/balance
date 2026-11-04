<?php

declare(strict_types=1);

namespace App\Security\Domain\Model\Account;

final class AccountFeatures
{
    public function __construct(
        private bool $accountTripCalculator = true,
        private bool $propertyTripCalculators = true,
        private bool $events = true,
        private bool $compensation = true,
        private bool $organizationalCarbonFootprint = true,
    ) {}

    public function isEnabled(AccountFeature $feature): bool
    {
        return match ($feature) {
            AccountFeature::AccountTripCalculator => $this->accountTripCalculator,
            AccountFeature::PropertyTripCalculators => $this->propertyTripCalculators,
            AccountFeature::Events => $this->events,
            AccountFeature::Compensation => $this->compensation,
            AccountFeature::OrganizationalCarbonFootprint => $this->organizationalCarbonFootprint,
        };
    }

    public function accountTripCalculator(): bool
    {
        return $this->accountTripCalculator;
    }

    public function propertyTripCalculators(): bool
    {
        return $this->propertyTripCalculators;
    }

    public function events(): bool
    {
        return $this->events;
    }

    public function compensation(): bool
    {
        return $this->compensation;
    }

    public function organizationalCarbonFootprint(): bool
    {
        return $this->organizationalCarbonFootprint;
    }

    public function equals(self $other): bool
    {
        return $this->accountTripCalculator === $other->accountTripCalculator()
            && $this->propertyTripCalculators === $other->propertyTripCalculators()
            && $this->events === $other->events()
            && $this->compensation === $other->compensation()
            && $this->organizationalCarbonFootprint === $other->organizationalCarbonFootprint();
    }
}
