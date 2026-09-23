<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Event\CompanyCarbonFootprint;

use App\CarbonFootprint\Application\CompanyCarbonFootprint\Complete\CompleteCompanyCarbonFootprintRequest;
use App\CarbonFootprint\Application\CompanyCarbonFootprint\Complete\CompleteCompanyCarbonFootprintService;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintScopeAnswered;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Event\DomainEventSubscriber;
use App\Shared\Domain\Event\RegisterTrait;

class CompleteCompanyCarbonFootprintOnCompanyCarbonFootprintScopeAnsweredSubscriber implements DomainEventSubscriber
{
    use RegisterTrait;

    public function __construct(private CompleteCompanyCarbonFootprintService $completeCompanyCarbonFootprint, private LoggedUser $loggedUser)
    {
        $this->register();
    }

    public function handle(DomainEvent $event): void
    {
        if (!$this->isSubscribedTo($event)) {
            throw new \RuntimeException('Incorrect event!');
        }

        assert($event instanceof CompanyCarbonFootprintScopeAnswered);

        if (Scope::Scope2 !== $event->scope()) {
            return;
        }

        ($this->completeCompanyCarbonFootprint)(new CompleteCompanyCarbonFootprintRequest($event->companyCarbonFootprintId()), $this->loggedUser->__invoke());
    }

    public function isSubscribedTo(DomainEvent $event): bool
    {
        return $event instanceof CompanyCarbonFootprintScopeAnswered;
    }
}
