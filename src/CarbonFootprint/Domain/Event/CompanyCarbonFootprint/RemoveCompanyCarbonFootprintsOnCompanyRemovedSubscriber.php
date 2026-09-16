<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Event\CompanyCarbonFootprint;

use App\CarbonFootprint\Application\CompanyCarbonFootprint\Remove\RemoveCompanyCarbonFootprintRequest;
use App\CarbonFootprint\Application\CompanyCarbonFootprint\Remove\RemoveCompanyCarbonFootprintService;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintRepository;
use App\Property\Domain\Model\Company\CompanyRemoved;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Event\DomainEventSubscriber;
use App\Shared\Domain\Event\RegisterTrait;

class RemoveCompanyCarbonFootprintsOnCompanyRemovedSubscriber implements DomainEventSubscriber
{
    use RegisterTrait;

    public function __construct(
        private CompanyCarbonFootprintRepository $companyCarbonFootprintRepository,
        private RemoveCompanyCarbonFootprintService $removeCompanyCarbonFootprint,
        private LoggedUser $loggedUser,
    ) {
        $this->register();
    }

    public function handle(DomainEvent $event): void
    {
        if (!$this->isSubscribedTo($event)) {
            throw new \RuntimeException('Incorrect event!');
        }

        assert($event instanceof CompanyRemoved);

        foreach ($this->companyCarbonFootprintRepository->findByCompanyId($event->companyId()) as $companyCarbonFootprint) {
            $request = new RemoveCompanyCarbonFootprintRequest($companyCarbonFootprint->id());

            ($this->removeCompanyCarbonFootprint)($request, $this->loggedUser->__invoke());
        }
    }

    public function isSubscribedTo(DomainEvent $event): bool
    {
        return $event instanceof CompanyRemoved;
    }
}
