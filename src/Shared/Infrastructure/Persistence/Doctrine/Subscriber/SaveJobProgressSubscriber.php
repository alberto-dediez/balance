<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Persistence\Doctrine\Subscriber;

use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Event\DomainEventSubscriber;
use App\Shared\Domain\Event\JobProgress\JobProgressAdvanced;
use App\Shared\Domain\Event\JobProgress\JobProgressCompleted;
use App\Shared\Domain\Event\JobProgress\JobProgressFailed;
use App\Shared\Domain\Event\RegisterTrait;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'dev')]
#[When(env: 'prod')]
final class SaveJobProgressSubscriber implements DomainEventSubscriber
{
    use RegisterTrait;

    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
        $this->register();
    }

    public function isSubscribedTo(DomainEvent $event): bool
    {
        return $event instanceof JobProgressAdvanced
            || $event instanceof JobProgressCompleted
            || $event instanceof JobProgressFailed;
    }

    public function handle(DomainEvent $event): void
    {
        $this->entityManager->flush();
    }
}
