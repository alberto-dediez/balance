<?php

declare(strict_types=1);

namespace App\Changelog\Application\Announcement\List;

use App\Changelog\Application\Announcement\AnnouncementAuthorizer;
use App\Changelog\Domain\Model\Announcement\Announcement;
use App\Changelog\Domain\Model\Announcement\AnnouncementId;
use App\Changelog\Domain\Model\Announcement\AnnouncementRepository;
use App\Changelog\Domain\Model\Announcement\AnnouncementStatus;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class ListAnnouncementsService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private readonly AnnouncementRepository $repository,
        private readonly AnnouncementAuthorizer $authorizer,
    ) {}

    public function __invoke(ListAnnouncementsRequest $request, User $user): ListAnnouncementsResponse
    {
        $subject = new Announcement(AnnouncementId::generate());

        $this->authorizer->denyUnlessCanDoThat($subject, Action::List, $user);

        if (!$this->authorizer->canDoThat($subject, Action::Admin, $user)) {
            $request->status = AnnouncementStatus::Published;
        }

        return new ListAnnouncementsResponse(
            $this->repository->findByFilter($request),
            $this->repository->totalResultsByFilter($request),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof ListAnnouncementsRequest;
    }
}
