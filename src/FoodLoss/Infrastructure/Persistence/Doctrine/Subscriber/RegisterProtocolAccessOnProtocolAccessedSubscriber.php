<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\Persistence\Doctrine\Subscriber;

use App\FoodLoss\Domain\Model\Protocol\ProtocolAccessed;
use App\FoodLoss\Domain\Model\ProtocolAccess\ProtocolAccess;
use App\FoodLoss\Domain\Model\ProtocolAccess\ProtocolAccessId;
use App\FoodLoss\Domain\Model\ProtocolAccess\ProtocolAccessRepository;
use App\HumanResources\Domain\Model\Employee\EmployeeRepository;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Event\DomainEventSubscriber;
use App\Shared\Domain\Event\RegisterTrait;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'dev')]
#[When(env: 'prod')]
final class RegisterProtocolAccessOnProtocolAccessedSubscriber implements DomainEventSubscriber
{
    use RegisterTrait;

    public function __construct(
        private readonly EmployeeRepository $employeeRepository,
        private readonly ProtocolAccessRepository $protocolAccessRepository,
    ) {
        $this->register();
    }

    public function isSubscribedTo(DomainEvent $event): bool
    {
        return $event instanceof ProtocolAccessed;
    }

    public function handle(DomainEvent $event): void
    {
        \assert($event instanceof ProtocolAccessed);

        $employee = $this->employeeRepository->findByUserId($event->userId());

        if (null === $employee) {
            return;
        }

        $protocolAccess = new ProtocolAccess(id: ProtocolAccessId::generate());
        $protocolAccess->register(
            protocolId: $event->protocolId(),
            employeeId: $employee->id(),
            accountId: $event->accountId(),
        );

        $this->protocolAccessRepository->add($protocolAccess);
    }
}
