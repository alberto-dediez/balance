<?php

declare(strict_types=1);

namespace App\Changelog\Application\Announcement\Update;

use App\Changelog\Application\Announcement\AnnouncementAuthorizer;
use App\Changelog\Domain\Model\Announcement\AnnouncementNotFoundException;
use App\Changelog\Domain\Model\Announcement\AnnouncementRepository;
use App\Changelog\Domain\Model\Announcement\YouTubeVideo;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;
use App\Shared\Domain\Model\Locale\LocaleGetter;

class UpdateAnnouncementService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private readonly AnnouncementRepository $repository,
        private readonly AnnouncementAuthorizer $authorizer,
    ) {}

    public function __invoke(UpdateAnnouncementRequest $request, User $user): UpdateAnnouncementResponse
    {
        $announcement = $this->repository->find($request->announcementId);

        if (null == $announcement) {
            throw new AnnouncementNotFoundException($request->announcementId);
        }

        $this->authorizer->denyUnlessCanDoThat($announcement, Action::Edit, $user);

        $locale = LocaleGetter::locale();
        $isDefaultLocale = LocaleGetter::default() === $locale;

        $youtubeVideo = null !== $request->youtubeUrl && '' !== trim($request->youtubeUrl)
            ? YouTubeVideo::fromUrl($request->youtubeUrl)
            : null;

        $announcement->update(
            category: $request->category,
            date: $request->date,
            version: $request->version,
            title: trim($request->title),
            summary: trim($request->summary),
            body: $request->body,
            youtubeVideo: $youtubeVideo,
            manualId: $request->manualId,
            locale: $isDefaultLocale ? null : $locale,
        );

        $this->repository->add($announcement);

        return new UpdateAnnouncementResponse($announcement->id(), $announcement->title());
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof UpdateAnnouncementRequest;
    }
}
