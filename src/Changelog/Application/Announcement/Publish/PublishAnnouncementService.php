<?php

declare(strict_types=1);

namespace App\Changelog\Application\Announcement\Publish;

use App\Changelog\Application\Announcement\AnnouncementAuthorizer;
use App\Changelog\Application\Announcement\Notify\NotifyAnnouncementRequest;
use App\Changelog\Domain\Model\Announcement\AnnouncementNotFoundException;
use App\Changelog\Domain\Model\Announcement\AnnouncementRepository;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandBusDispatcher;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\DeferredCommand;
use App\Shared\Application\CommandBus\Priority;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class PublishAnnouncementService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private readonly AnnouncementRepository $repository,
        private readonly AnnouncementAuthorizer $authorizer,
        private readonly bool $announcementsEnabled = true,
    ) {}

    public function __invoke(PublishAnnouncementRequest $request, User $user): PublishAnnouncementResponse
    {
        $announcement = $this->repository->find($request->announcementId);

        if (null == $announcement) {
            throw new AnnouncementNotFoundException($request->announcementId);
        }

        $this->authorizer->denyUnlessCanDoThat($announcement, Action::Edit, $user);

        if ($announcement->isPublished()) {
            return new PublishAnnouncementResponse($announcement->id(), $announcement->status(), false);
        }

        $notifyUsers = $this->announcementsEnabled && $request->notifyUsers;

        if ($request->publishAt instanceof \DateTimeImmutable && $request->publishAt > $request->now) {
            $announcement->schedule($request->publishAt, $notifyUsers);

            $this->repository->add($announcement);

            return new PublishAnnouncementResponse($announcement->id(), $announcement->status(), false, $announcement->scheduledAt());
        }

        $announcement->publish($notifyUsers);

        $this->repository->add($announcement);

        if ($notifyUsers) {
            CommandBusDispatcher::instance()->dispatch(
                new DeferredCommand(
                    command: new NotifyAnnouncementRequest($announcement->id()),
                    priority: Priority::LOW,
                ),
                $user,
            );
        }

        return new PublishAnnouncementResponse($announcement->id(), $announcement->status(), $notifyUsers);
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof PublishAnnouncementRequest;
    }
}
