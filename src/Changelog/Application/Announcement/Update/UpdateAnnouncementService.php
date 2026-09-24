<?php

declare(strict_types=1);

namespace App\Changelog\Application\Announcement\Update;

use App\Changelog\Application\Announcement\AnnouncementAuthorizer;
use App\Changelog\Domain\Model\Announcement\Announcement;
use App\Changelog\Domain\Model\Announcement\AnnouncementManualStorage;
use App\Changelog\Domain\Model\Announcement\AnnouncementNotFoundException;
use App\Changelog\Domain\Model\Announcement\AnnouncementRepository;
use App\Changelog\Domain\Model\Announcement\AnnouncementTranslation;
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
        private readonly AnnouncementManualStorage $storage,
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

        $translation = $announcement->translations()->getTranslationFor($locale);

        $currentManualPath = $isDefaultLocale ? $announcement->manualPath($locale) : ($translation instanceof AnnouncementTranslation ? $translation->manualPath() : null);
        $currentManualFileName = $isDefaultLocale ? $announcement->manualFileName($locale) : ($translation instanceof AnnouncementTranslation ? $translation->manualFileName() : null);

        $manualPath = $currentManualPath;
        $manualFileName = $currentManualFileName;

        if (null !== $request->manualLocalPath) {
            $manualPath = $this->storage->store($announcement->id(), $locale, $request->manualLocalPath);
            $manualFileName = $request->manualFileName ?? basename($request->manualLocalPath);
        } elseif ($request->removeManual) {
            $manualPath = null;
            $manualFileName = null;
        }

        $announcement->update(
            category: $request->category,
            date: $request->date,
            version: $request->version,
            title: trim($request->title),
            summary: trim($request->summary),
            body: $request->body,
            youtubeVideo: $youtubeVideo,
            manualFileName: $manualFileName,
            manualPath: $manualPath,
            locale: $isDefaultLocale ? null : $locale,
        );

        $this->repository->add($announcement);

        if (null !== $currentManualPath && !$this->isManualInUse($announcement, $currentManualPath)) {
            $this->storage->delete($currentManualPath);
        }

        return new UpdateAnnouncementResponse($announcement->id(), $announcement->title());
    }

    private function isManualInUse(Announcement $announcement, string $manualPath): bool
    {
        if ($announcement->manualPath(LocaleGetter::default()) === $manualPath) {
            return true;
        }

        foreach ($announcement->translations() as $translation) {
            if ($translation instanceof AnnouncementTranslation && $translation->manualPath() === $manualPath) {
                return true;
            }
        }

        return false;
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof UpdateAnnouncementRequest;
    }
}
