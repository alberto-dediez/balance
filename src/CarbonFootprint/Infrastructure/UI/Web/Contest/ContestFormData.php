<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Contest;

use App\CarbonFootprint\Domain\Model\Contest\Contest;
use App\CarbonFootprint\Domain\Model\Contest\ContestAwardMode;
use App\CarbonFootprint\Domain\Model\Contest\ContestHostReference;
use App\CarbonFootprint\Domain\Model\Contest\ContestHostType;
use App\CarbonFootprint\Domain\Model\Contest\ContestParticipationMode;
use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLinkId;

class ContestFormData
{
    /**
     * @param list<string> $eventIds
     * @param list<string> $propertyIds
     * @param list<string> $allowedCountries
     */
    public function __construct(
        public bool $accountTripCalculator = false,
        public array $eventIds = [],
        public array $propertyIds = [],
        public ContestParticipationMode $participationMode = ContestParticipationMode::Calculation,
        public ContestAwardMode $awardMode = ContestAwardMode::Draw,
        public ?int $winnersCount = 1,
        public bool $platformDraw = false,
        public bool $adultsOnly = true,
        public array $allowedCountries = [],
        public ?\DateTimeImmutable $startsAt = null,
        public ?\DateTimeImmutable $endsAt = null,
        public string $name = '',
        public string $prizeDescription = '',
        public string $description = '',
        public string $terms = '',
        public string $organizerName = '',
        public string $organizerTaxId = '',
        public string $organizerContactEmail = '',
        public ?string $privacyPolicyUrl = null,
    ) {}

    public static function fromContest(Contest $contest): self
    {
        $eventIds = [];
        $propertyIds = [];
        $accountTripCalculator = false;

        foreach ($contest->hostReferences() as $host) {
            match ($host->hostType) {
                ContestHostType::Event => $eventIds[] = $host->hostId,
                ContestHostType::Property => $propertyIds[] = $host->hostId,
                ContestHostType::TripCalculationLink => $accountTripCalculator = true,
            };
        }

        return new self(
            accountTripCalculator: $accountTripCalculator,
            eventIds: $eventIds,
            propertyIds: $propertyIds,
            participationMode: $contest->participationMode(),
            awardMode: $contest->awardMode(),
            winnersCount: $contest->winnersCount(),
            platformDraw: $contest->platformDraw(),
            adultsOnly: $contest->adultsOnly(),
            allowedCountries: $contest->allowedCountryIsos(),
            startsAt: $contest->startsAt(),
            endsAt: $contest->endsAt(),
            name: $contest->name(),
            prizeDescription: $contest->prizeDescription(),
            description: $contest->description(),
            terms: $contest->terms(),
            organizerName: $contest->organizerName(),
            organizerTaxId: $contest->organizerTaxId(),
            organizerContactEmail: $contest->organizerContactEmail(),
            privacyPolicyUrl: $contest->privacyPolicyUrl(),
        );
    }

    /** @return list<ContestHostReference> */
    public function hosts(?TripCalculationLinkId $accountTripCalculationLinkId): array
    {
        $hosts = [];

        foreach ($this->eventIds as $eventId) {
            $hosts[] = new ContestHostReference(ContestHostType::Event, $eventId);
        }

        foreach ($this->propertyIds as $propertyId) {
            $hosts[] = new ContestHostReference(ContestHostType::Property, $propertyId);
        }

        if ($this->accountTripCalculator && null !== $accountTripCalculationLinkId) {
            $hosts[] = new ContestHostReference(ContestHostType::TripCalculationLink, (string) $accountTripCalculationLinkId);
        }

        return $hosts;
    }

    public function startsAt(): \DateTimeImmutable
    {
        return $this->startsAt ?? new \DateTimeImmutable('NOW');
    }

    public function endsAt(): \DateTimeImmutable
    {
        return $this->endsAt ?? new \DateTimeImmutable('NOW');
    }
}
