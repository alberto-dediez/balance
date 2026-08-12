<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\PublicLanding;

use App\CarbonFootprint\Domain\Model\PublicLanding\PublicLandingOwnerType;
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
    ) {}

    public function accountId(PublicLandingOwnerType $ownerType, string $ownerId): AccountId
    {
        $accountId = $ownerType->isProperty()
            ? $this->propertyRepository->find(new PropertyId($ownerId))?->accountId()
            : $this->eventRepository->find(new EventId($ownerId))?->accountId();

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

        $event = $this->eventRepository->find(new EventId($ownerId));

        if (null === $event) {
            throw new \InvalidArgumentException('Event not found');
        }

        $this->eventAuthorizer->denyUnlessCanDoThat($event, $action, $user);
    }
}
