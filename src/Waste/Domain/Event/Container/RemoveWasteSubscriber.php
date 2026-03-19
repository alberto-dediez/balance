<?php

declare(strict_types=1);

namespace App\Waste\Domain\Event\Container;

use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Event\DomainEventSubscriber;
use App\Shared\Domain\Event\RegisterTrait;
use App\Waste\Application\Container\Remove\RemoveContainerRequest;
use App\Waste\Application\Container\Remove\RemoveContainerService;
use App\Waste\Domain\Model\Container\ContainerFilter;
use App\Waste\Domain\Model\Container\ContainerRepository;
use App\Waste\Domain\Model\Waste\WasteRemoved;

class RemoveWasteSubscriber implements DomainEventSubscriber
{
    use RegisterTrait;

    public function __construct(private ContainerRepository $containerRepository, private RemoveContainerService $removeContainer, private LoggedUser $loggedUser)
    {
        $this->register();
    }

    public function handle(DomainEvent $event): void
    {
        if (!$this->isSubscribedTo($event)) {
            throw new \RuntimeException('Incorrect event!');
        }

        $this->handlePropertyRemoved($event);
    }

    private function handlePropertyRemoved(WasteRemoved $event): void
    {
        $containers = $this->containerRepository->findByFilter(
            new ContainerFilter(wasteId: $event->wasteId()),
        );

        if (0 === count($containers)) {
            return;
        }

        $removeContainer = $this->removeContainer;

        $user = $this->loggedUser->__invoke();

        foreach ($containers as $container) {
            $request = new RemoveContainerRequest(
                $container->id(),
            );

            $removeContainer($request, $user);
        }
    }

    public function isSubscribedTo(DomainEvent $event): bool
    {
        return $event instanceof WasteRemoved;
    }
}
