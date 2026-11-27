<?php

declare(strict_types=1);

namespace App\Library\Domain\Event\Backup;

use App\Library\Domain\Model\Backup\MediaBackupRepository;
use App\Library\Domain\Model\Media\MediaRemoved;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Event\DomainEventSubscriber;
use App\Shared\Domain\Event\RegisterTrait;

class MarkMediaBackupRemovedOnMediaRemoved implements DomainEventSubscriber
{
    use RegisterTrait;

    public function __construct(private readonly MediaBackupRepository $backups)
    {
        $this->register();
    }

    public function handle(DomainEvent $event): void
    {
        if (!$event instanceof MediaRemoved) {
            throw new \RuntimeException('Incorrect event!');
        }

        if ('' === $event->path()) {
            return;
        }

        $backup = $this->backups->find($event->path());

        if (null === $backup) {
            return;
        }

        $backup->markRemoved($event->occurredOn());
        $this->backups->save($backup);
    }

    public function isSubscribedTo(DomainEvent $event): bool
    {
        return $event instanceof MediaRemoved;
    }
}
