<?php

declare(strict_types=1);

namespace App\Changelog\Application\Announcement\DownloadManual;

use App\Changelog\Application\Announcement\AnnouncementAuthorizer;
use App\Changelog\Domain\Model\Announcement\AnnouncementNotFoundException;
use App\Changelog\Domain\Model\Announcement\AnnouncementRepository;
use App\Changelog\Domain\Model\ChangelogEntry\ManualNotFoundException;
use App\Library\Domain\Model\Media\MediaId;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;
use App\Shared\Domain\Model\Locale\LocaleGetter;

class DownloadAnnouncementManualService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private readonly AnnouncementRepository $repository,
        private readonly AnnouncementAuthorizer $authorizer,
    ) {}

    public function __invoke(DownloadAnnouncementManualRequest $request, User $user): DownloadAnnouncementManualResponse
    {
        $announcement = $this->repository->find($request->announcementId);

        if (null == $announcement) {
            throw new AnnouncementNotFoundException($request->announcementId);
        }

        $this->authorizer->denyUnlessCanDoThat($announcement, Action::View, $user);

        $mediaId = $announcement->manualId($request->locale ?? LocaleGetter::locale());

        if (!$mediaId instanceof MediaId) {
            throw new ManualNotFoundException('');
        }

        return new DownloadAnnouncementManualResponse($mediaId);
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof DownloadAnnouncementManualRequest;
    }
}
