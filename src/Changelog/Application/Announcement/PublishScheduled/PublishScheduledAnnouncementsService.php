<?php

declare(strict_types=1);

namespace App\Changelog\Application\Announcement\PublishScheduled;

use App\Changelog\Application\Announcement\AnnouncementAuthorizer;
use App\Changelog\Application\Announcement\Notify\NotifyAnnouncementRequest;
use App\Changelog\Domain\Model\Announcement\Announcement;
use App\Changelog\Domain\Model\Announcement\AnnouncementId;
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

class PublishScheduledAnnouncementsService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private readonly AnnouncementRepository $repository,
        private readonly AnnouncementAuthorizer $authorizer,
        private readonly bool $announcementsEnabled = true,
    ) {}

    public function __invoke(PublishScheduledAnnouncementsRequest $request, User $user): PublishScheduledAnnouncementsResponse
    {
        $this->authorizer->denyUnlessCanDoThat(new Announcement(AnnouncementId::generate()), Action::Admin, $user);

        $published = 0;
        $notified = 0;

        foreach ($this->repository->findScheduledToPublishUntil($request->now) as $announcement) {
            $notify = $this->announcementsEnabled && $announcement->notifyOnPublish();

            $announcement->publish($notify);

            $this->repository->add($announcement);

            ++$published;

            if (!$notify) {
                continue;
            }

            CommandBusDispatcher::instance()->dispatch(
                new DeferredCommand(
                    command: new NotifyAnnouncementRequest($announcement->id()),
                    priority: Priority::LOW,
                ),
                $user,
            );

            ++$notified;
        }

        return new PublishScheduledAnnouncementsResponse(published: $published, notified: $notified);
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof PublishScheduledAnnouncementsRequest;
    }
}
