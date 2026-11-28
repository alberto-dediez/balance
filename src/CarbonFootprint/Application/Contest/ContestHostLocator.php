<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Contest;

use App\CarbonFootprint\Application\TripCalculationLink\TripCalculationLinkAuthorizer;
use App\CarbonFootprint\Domain\Model\Contest\ContestHostReference;
use App\CarbonFootprint\Domain\Model\Contest\ContestHostType;
use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLinkId;
use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLinkRepository;
use App\EventOperations\Application\Event\EventAuthorizer;
use App\EventOperations\Domain\Model\Event\EventId;
use App\EventOperations\Domain\Model\Event\EventRepository;
use App\Property\Application\Property\PropertyAuthorizer;
use App\Property\Domain\Model\Property\PropertyId;
use App\Property\Domain\Model\Property\PropertyRepository;
use App\Security\Application\Action;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\User;

final class ContestHostLocator
{
    public const string ACCOUNT_TRIP_CALCULATOR_NAME = 'Calculadora de viajes de la cuenta';
    public const string MISSING_HOST_NAME = '---';

    public function __construct(
        private readonly PropertyRepository $propertyRepository,
        private readonly PropertyAuthorizer $propertyAuthorizer,
        private readonly EventRepository $eventRepository,
        private readonly EventAuthorizer $eventAuthorizer,
        private readonly TripCalculationLinkRepository $tripCalculationLinkRepository,
        private readonly TripCalculationLinkAuthorizer $tripCalculationLinkAuthorizer,
    ) {}

    public function accountId(ContestHostReference $host): ?AccountId
    {
        return match ($host->hostType) {
            ContestHostType::Property => $this->propertyRepository->find(new PropertyId($host->hostId))?->accountId(),
            ContestHostType::Event => $this->eventRepository->find(new EventId($host->hostId))?->accountId(),
            ContestHostType::TripCalculationLink => $this->tripCalculationLinkRepository->find(new TripCalculationLinkId($host->hostId))?->accountId(),
        };
    }

    public function name(ContestHostReference $host): string
    {
        return match ($host->hostType) {
            ContestHostType::Property => $this->propertyRepository->find(new PropertyId($host->hostId))?->name() ?? self::MISSING_HOST_NAME,
            ContestHostType::Event => $this->eventRepository->find(new EventId($host->hostId))?->name() ?? self::MISSING_HOST_NAME,
            ContestHostType::TripCalculationLink => null !== $this->tripCalculationLinkRepository->find(new TripCalculationLinkId($host->hostId))
                ? self::ACCOUNT_TRIP_CALCULATOR_NAME
                : self::MISSING_HOST_NAME,
        };
    }

    public function denyUnlessCanEdit(ContestHostReference $host, User $user): void
    {
        if (ContestHostType::Property === $host->hostType) {
            $property = $this->propertyRepository->find(new PropertyId($host->hostId));

            if (null === $property) {
                throw new \InvalidArgumentException('Property not found');
            }

            $this->propertyAuthorizer->denyUnlessCanDoThat($property, Action::Edit, $user);

            return;
        }

        if (ContestHostType::TripCalculationLink === $host->hostType) {
            $tripCalculationLink = $this->tripCalculationLinkRepository->find(new TripCalculationLinkId($host->hostId));

            if (null === $tripCalculationLink) {
                throw new \InvalidArgumentException('Trip calculation link not found');
            }

            $this->tripCalculationLinkAuthorizer->denyUnlessCanDoThat($tripCalculationLink, Action::Edit, $user);

            return;
        }

        $event = $this->eventRepository->find(new EventId($host->hostId));

        if (null === $event) {
            throw new \InvalidArgumentException('Event not found');
        }

        $this->eventAuthorizer->denyUnlessCanDoThat($event, Action::Edit, $user);
    }
}
