<?php

declare(strict_types=1);

namespace App\Changelog\Application\Announcement\View;

use App\Changelog\Application\Announcement\AnnouncementAuthorizer;
use App\Changelog\Domain\Model\Announcement\AnnouncementNotFoundException;
use App\Changelog\Domain\Model\Announcement\AnnouncementRepository;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class ViewAnnouncementService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private readonly AnnouncementRepository $repository,
        private readonly AnnouncementAuthorizer $authorizer,
    ) {}

    public function __invoke(ViewAnnouncementRequest $request, User $user): ViewAnnouncementResponse
    {
        $announcement = $this->repository->find($request->announcementId);

        if (null == $announcement) {
            throw new AnnouncementNotFoundException($request->announcementId);
        }

        $this->authorizer->denyUnlessCanDoThat($announcement, Action::View, $user);

        return new ViewAnnouncementResponse($announcement);
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof ViewAnnouncementRequest;
    }
}
