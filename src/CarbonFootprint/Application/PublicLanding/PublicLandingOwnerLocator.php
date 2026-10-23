<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\PublicLanding;

use App\CarbonFootprint\Application\TripCalculationLink\TripCalculationLinkAuthorizer;
use App\CarbonFootprint\Domain\Model\PublicLanding\PublicLandingOwnerType;
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

final class PublicLandingOwnerLocator
{
    public function __construct(
        private readonly PropertyRepository $propertyRepository,
        private readonly PropertyAuthorizer $propertyAuthorizer,
        private readonly EventRepository $eventRepository,
        private readonly EventAuthorizer $eventAuthorizer,
        private readonly TripCalculationLinkRepository $tripCalculationLinkRepository,
        private readonly TripCalculationLinkAuthorizer $tripCalculationLinkAuthorizer,
    ) {}

    public function accountId(PublicLandingOwnerType $ownerType, string $ownerId): AccountId
    {
        $accountId = match ($ownerType) {
            PublicLandingOwnerType::Property => $this->propertyRepository->find(new PropertyId($ownerId))?->accountId(),
            PublicLandingOwnerType::Event => $this->eventRepository->find(new EventId($ownerId))?->accountId(),
            PublicLandingOwnerType::TripCalculationLink => $this->tripCalculationLinkRepository->find(new TripCalculationLinkId($ownerId))?->accountId(),
        };

        if (!$accountId instanceof AccountId) {
            throw new \InvalidArgumentException('Public landing owner not found or has no accountId');
        }

        return $accountId;
    }

    public function denyUnlessCanDoThat(PublicLandingOwnerType $ownerType, string $ownerId, Action $action, User $user): void
    {
        if ($ownerType->isProperty()) {
            $property = $this->propertyRepository->find(new PropertyId($ownerId));

            if (null === $property) {
                throw new \InvalidArgumentException('Property not found');
            }

            $this->propertyAuthorizer->denyUnlessCanDoThat($property, $action, $user);

            return;
        }

        if ($ownerType->isTripCalculationLink()) {
            $tripCalculationLink = $this->tripCalculationLinkRepository->find(new TripCalculationLinkId($ownerId));

            if (null === $tripCalculationLink) {
                throw new \InvalidArgumentException('Trip calculation link not found');
            }

            $this->tripCalculationLinkAuthorizer->denyUnlessCanDoThat($tripCalculationLink, $action, $user);

            return;
        }

        $event = $this->eventRepository->find(new EventId($ownerId));

        if (null === $event) {
            throw new \InvalidArgumentException('Event not found');
        }

        $this->eventAuthorizer->denyUnlessCanDoThat($event, $action, $user);
    }
}
