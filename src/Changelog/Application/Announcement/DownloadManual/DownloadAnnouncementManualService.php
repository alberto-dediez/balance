<?php

declare(strict_types=1);

namespace App\Changelog\Application\Announcement\DownloadManual;

use App\Changelog\Application\Announcement\AnnouncementAuthorizer;
use App\Changelog\Domain\Model\Announcement\AnnouncementManualStorage;
use App\Changelog\Domain\Model\Announcement\AnnouncementNotFoundException;
use App\Changelog\Domain\Model\Announcement\AnnouncementRepository;
use App\Changelog\Domain\Model\ChangelogEntry\ManualNotFoundException;
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
        private readonly AnnouncementManualStorage $storage,
        private readonly AnnouncementAuthorizer $authorizer,
    ) {}

    public function __invoke(DownloadAnnouncementManualRequest $request, User $user): DownloadAnnouncementManualResponse
    {
        $announcement = $this->repository->find($request->announcementId);

        if (null == $announcement) {
            throw new AnnouncementNotFoundException($request->announcementId);
        }

        $this->authorizer->denyUnlessCanDoThat($announcement, Action::View, $user);

        $locale = $request->locale ?? LocaleGetter::locale();

        $path = $announcement->manualPath($locale);

        if (null === $path || !$this->storage->exists($path)) {
            throw new ManualNotFoundException($path ?? '');
        }

        return new DownloadAnnouncementManualResponse(
            stream: $this->storage->read($path),
            fileName: $announcement->manualFileName($locale) ?? basename($path),
            size: $this->storage->size($path),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof DownloadAnnouncementManualRequest;
    }
}
