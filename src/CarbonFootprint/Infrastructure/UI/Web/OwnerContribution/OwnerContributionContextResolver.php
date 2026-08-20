<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\OwnerContribution;

use App\Balance\Domain\Model\ProjectContribution\ProjectContributionOrigin;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprint;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintRepository;
use App\EventOperations\Domain\Model\Event\EventId;
use App\EventOperations\Domain\Model\Event\EventRepository;
use App\Property\Domain\Model\Property\PropertyId;
use App\Property\Domain\Model\Property\PropertyRepository;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\Account\AccountRepository;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CurrentUser\CurrentUserContext;

final class OwnerContributionContextResolver
{
    public function __construct(
        private EventRepository $eventRepository,
        private EventCarbonFootprintRepository $eventCarbonFootprintRepository,
        private PropertyRepository $propertyRepository,
        private AccountRepository $accountRepository,
        private CurrentUserContext $currentUserContext = new CurrentUserContext(),
    ) {}

    public function __invoke(string $origin, string $originId, User $user): ?OwnerContributionContext
    {
        $accountId = $this->currentUserContext->accountId($user);

        if (!$this->currentUserContext->hasRole($user, Role::Owner) || !$accountId instanceof AccountId) {
            return null;
        }

        return match (ProjectContributionOrigin::tryFrom($origin)) {
            ProjectContributionOrigin::EventCarbonFootprint => $this->fromEvent($accountId, $originId),
            ProjectContributionOrigin::Property => $this->fromProperty($accountId, $originId),
            ProjectContributionOrigin::Account => $this->fromAccount($accountId, $originId),
            default => null,
        };
    }

    private function fromEvent(AccountId $accountId, string $eventId): ?OwnerContributionContext
    {
        $event = $this->eventRepository->find(new EventId($eventId));
        $eventAccountId = $event?->accountId();

        if (null === $event || !$eventAccountId instanceof AccountId || !$accountId->equals($eventAccountId)) {
            return null;
        }

        $footprint = $this->eventCarbonFootprintRepository->findByEventId($event->id());

        if (!$footprint instanceof EventCarbonFootprint || !$footprint->status()->isCompleted()) {
            return null;
        }

        return new OwnerContributionContext(
            accountId: $accountId,
            origin: ProjectContributionOrigin::EventCarbonFootprint,
            originId: $eventId,
            originName: $event->name(),
            suggestedCo2eqKg: (float) $footprint->totalEmissions()->co2eq(),
        );
    }

    private function fromProperty(AccountId $accountId, string $propertyId): ?OwnerContributionContext
    {
        $property = $this->propertyRepository->find(new PropertyId($propertyId));
        $propertyAccountId = $property?->accountId();

        if (null === $property || !$propertyAccountId instanceof AccountId || !$accountId->equals($propertyAccountId)) {
            return null;
        }

        return new OwnerContributionContext(
            accountId: $accountId,
            origin: ProjectContributionOrigin::Property,
            originId: $propertyId,
            originName: $property->name(),
            suggestedCo2eqKg: null,
        );
    }

    private function fromAccount(AccountId $accountId, string $originId): ?OwnerContributionContext
    {
        if ((string) $accountId !== $originId) {
            return null;
        }

        $account = $this->accountRepository->find($accountId);

        if (null === $account) {
            return null;
        }

        return new OwnerContributionContext(
            accountId: $accountId,
            origin: ProjectContributionOrigin::Account,
            originId: $originId,
            originName: $account->name(),
            suggestedCo2eqKg: null,
        );
    }
}
