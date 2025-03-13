<?php

namespace App\Waste\Domain\Event\Container;

use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Event\RegisterTrait;
use App\Waste\Domain\Model\Waste\WasteRemoved;
use App\Shared\Domain\Event\DomainEventSubscriber;
use App\Waste\Domain\Model\Container\ContainerFilter;
use App\Waste\Domain\Model\Container\ContainerRepository;
use App\Waste\Application\Container\Remove\RemoveContainerRequest;
use App\Waste\Application\Container\Remove\RemoveContainerService;

class RemoveWasteSubscriber implements DomainEventSubscriber
{
    use RegisterTrait;

    public function __construct(private ContainerRepository $containerRepository, private RemoveContainerService $removeContainer)
    {
        $this->register();
    }

    public function handle(DomainEvent $event)
    {
        if (!$this->isSubscribedTo($event)) {
            throw new \RuntimeException("Incorrect event!");
        }

        $this->handlePropertyRemoved($event);
    }

    private function handlePropertyRemoved(WasteRemoved $event)
    {
        $containers = $this->containerRepository->findByFilter(
            new ContainerFilter(wasteId: $event->wasteId())
        );

        if (count($containers) == 0) {
            return ;
        }

        $removeContainer = $this->removeContainer;

        foreach ($containers as $container) {
            $request = new RemoveContainerRequest(
                $container->id()
            );

            $removeContainer($request);
        }
    }

    public function isSubscribedTo(DomainEvent $event)
    {
        return $event instanceof WasteRemoved;
    }
}
