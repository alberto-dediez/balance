<?php

declare(strict_types=1);

namespace App\Changelog\Application\Announcement\Register;

use App\Changelog\Application\Announcement\AnnouncementAuthorizer;
use App\Changelog\Domain\Model\Announcement\Announcement;
use App\Changelog\Domain\Model\Announcement\AnnouncementId;
use App\Changelog\Domain\Model\Announcement\AnnouncementRepository;
use App\Changelog\Domain\Model\Announcement\YouTubeVideo;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;
use App\Shared\Domain\Model\Locale\LocaleGetter;

class RegisterAnnouncementService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private readonly AnnouncementRepository $repository,
        private readonly AnnouncementAuthorizer $authorizer,
    ) {}

    public function __invoke(RegisterAnnouncementRequest $request, User $user): RegisterAnnouncementResponse
    {
        $announcement = new Announcement(AnnouncementId::generate());

        $this->authorizer->denyUnlessCanDoThat($announcement, Action::Register, $user);

        $locale = LocaleGetter::locale();
        $isDefaultLocale = LocaleGetter::default() === $locale;

        $youtubeVideo = null !== $request->youtubeUrl && '' !== trim($request->youtubeUrl)
            ? YouTubeVideo::fromUrl($request->youtubeUrl)
            : null;

        $announcement->register(
            category: $request->category,
            date: $request->date,
            version: $request->version,
            title: trim($request->title),
            summary: trim($request->summary),
            body: $request->body,
            youtubeVideo: $youtubeVideo,
            manualId: $request->manualId,
            registeredBy: $user->id(),
            locale: $isDefaultLocale ? null : $locale,
        );

        $this->repository->add($announcement);

        return new RegisterAnnouncementResponse($announcement->id(), $announcement->title());
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RegisterAnnouncementRequest;
    }
}
