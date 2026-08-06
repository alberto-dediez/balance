<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\TripCalculationLink;

use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLink;
use App\Property\Application\Property\PropertyAuthorizer;
use App\Property\Domain\Model\Property\PropertyRepository;
use App\Security\Application\AbstractAuthorizer;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;

final class TripCalculationLinkAuthorizer extends AbstractAuthorizer
{
    public function __construct(
        private readonly PropertyRepository $propertyRepository,
        private readonly PropertyAuthorizer $propertyAuthorizer,
    ) {}

    protected function canRegister(mixed $subject, User $user): bool
    {
        return $this->canDoThatOnProperty($subject, Action::Edit, $user);
    }

    protected function canView(mixed $subject, User $user): bool
    {
        return $this->canDoThatOnProperty($subject, Action::View, $user);
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
        return $subject instanceof TripCalculationLink;
    }

    private function canDoThatOnProperty(TripCalculationLink $tripCalculationLink, Action $action, User $user): bool
    {
        $propertyId = $tripCalculationLink->propertyId();
        $property = null !== $propertyId ? $this->propertyRepository->find($propertyId) : null;

        if (null === $property) {
            return false;
        }

        return $this->propertyAuthorizer->canDoThat($property, $action, $user);
    }
}
