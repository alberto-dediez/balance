<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\PublicLanding;

use App\CarbonFootprint\Domain\Model\PublicLanding\PublicLanding;
use App\CarbonFootprint\Domain\Model\PublicLanding\PublicLandingOwnerType;
use App\EventOperations\Application\Event\EventAuthorizer;
use App\EventOperations\Domain\Model\Event\EventId;
use App\EventOperations\Domain\Model\Event\EventRepository;
use App\Property\Application\Property\PropertyAuthorizer;
use App\Property\Domain\Model\Property\PropertyId;
use App\Property\Domain\Model\Property\PropertyRepository;
use App\Security\Application\AbstractAuthorizer;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;

final class PublicLandingAuthorizer extends AbstractAuthorizer
{
    public function __construct(
        private readonly PropertyRepository $propertyRepository,
        private readonly PropertyAuthorizer $propertyAuthorizer,
        private readonly EventRepository $eventRepository,
        private readonly EventAuthorizer $eventAuthorizer,
    ) {}

    protected function canRegister(mixed $subject, User $user): bool
    {
        return $this->canDoThatOnOwner($subject, Action::Edit, $user);
    }

    protected function canView(mixed $subject, User $user): bool
    {
        return $this->canDoThatOnOwner($subject, Action::View, $user);
    }

    protected function canList(mixed $subject, User $user): bool
    {
        return $this->canView($subject, $user);
    }

    protected function canEdit(mixed $subject, User $user): bool
    {
        return $this->canRegister($subject, $user);
    }

    protected function canRemove(mixed $subject, User $user): bool
    {
        return $this->canRegister($subject, $user);
    }

    public function supports(mixed $subject): bool
    {
        return $subject instanceof PublicLanding;
    }

    private function canDoThatOnOwner(PublicLanding $publicLanding, Action $action, User $user): bool
    {
        $ownerType = $publicLanding->ownerType();

        if (PublicLandingOwnerType::Property === $ownerType) {
            $property = $this->propertyRepository->find(new PropertyId($publicLanding->ownerId()));

            return null !== $property && $this->propertyAuthorizer->canDoThat($property, $action, $user);
        }

        if (PublicLandingOwnerType::Event === $ownerType) {
            $event = $this->eventRepository->find(new EventId($publicLanding->ownerId()));

            return null !== $event && $this->eventAuthorizer->canDoThat($event, $action, $user);
        }

        return false;
    }
}
