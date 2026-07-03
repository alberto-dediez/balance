<?php

declare(strict_types=1);

namespace App\DataExchange\Domain\Event\Importation;

use App\DataExchange\Application\ImportationProfile\Memorize\MemorizeImportationChoicesRequest;
use App\DataExchange\Application\ImportationProfile\Memorize\MemorizeImportationChoicesService;
use App\DataExchange\Domain\Model\Importation\Importation;
use App\DataExchange\Domain\Model\Importation\ImportationExecuted;
use App\DataExchange\Domain\Model\Importation\ImportationRepository;
use App\Security\Domain\Model\User\User;
use App\Security\Domain\Model\User\UserId;
use App\Security\Domain\Model\User\UserRepository;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Event\DomainEventSubscriber;
use App\Shared\Domain\Event\RegisterTrait;

class MemorizeChoicesOnImportationExecuted implements DomainEventSubscriber
{
    use RegisterTrait;

    public function __construct(
        private ImportationRepository $importationRepository,
        private UserRepository $userRepository,
        private MemorizeImportationChoicesService $memorizeImportationChoices,
    ) {
        $this->register();
    }

    public function handle(DomainEvent $event): void
    {
        if (!$this->isSubscribedTo($event)) {
            throw new \RuntimeException('Incorrect event!');
        }

        assert($event instanceof ImportationExecuted);

        $this->handleImportationExecuted($event);
    }

    private function handleImportationExecuted(ImportationExecuted $event): void
    {
        $importation = $this->importationRepository->find($event->importationId());

        if (!$importation instanceof Importation) {
            return;
        }

        $userId = $importation->registeredBy();

        if (!$userId instanceof UserId) {
            return;
        }

        $user = $this->userRepository->find($userId);

        if (!$user instanceof User) {
            return;
        }

        $memorizeImportationChoices = $this->memorizeImportationChoices;

        $memorizeImportationChoices(
            new MemorizeImportationChoicesRequest(
                importationId: $importation->id(),
            ),
            $user,
        );
    }

    public function isSubscribedTo(DomainEvent $event): bool
    {
        return $event instanceof ImportationExecuted;
    }
}
