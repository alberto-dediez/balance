<?php

namespace App\Waste\Domain\Event\Container;

use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Event\RegisterTrait;
use App\Shared\Domain\Event\DomainEventSubscriber;
use App\Property\Domain\Model\Property\PropertyRemoved;
use App\Waste\Domain\Model\Container\ContainerFilter;
use App\Waste\Domain\Model\Container\ContainerRepository;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Waste\Application\Container\Remove\RemoveContainerRequest;
use App\Waste\Application\Container\Remove\RemoveContainerService;

class RemovePropertySubscriber implements DomainEventSubscriber
{
    use RegisterTrait;

    public function __construct(private ContainerRepository $containerRepository, private RemoveContainerService $removeContainer, private LoggedUser $loggedUser)
    {
        $this->register();
    }

    public function handle(DomainEvent $event): void
    {
        if (!$this->isSubscribedTo($event)) {
            throw new \RuntimeException("Incorrect event!");
        }

        $this->handlePropertyRemoved($event);
    }

    private function handlePropertyRemoved(PropertyRemoved $event): void
    {
        $containers = $this->containerRepository->findByFilter(
            new ContainerFilter(propertyId: $event->propertyId())
        );

        if (count($containers) === 0) {
            return ;
        }

        $removeContainer = $this->removeContainer;

        $user = $this->loggedUser->__invoke();

        foreach ($containers as $container) {
            $request = new RemoveContainerRequest(
                $container->id()
            );

            $removeContainer($request, $user);
        }
    }

    public function isSubscribedTo(DomainEvent $event): bool
    {
        return $event instanceof PropertyRemoved;
    }
}
