<?php

declare(strict_types=1);

namespace App\Changelog\Application\Announcement\Notify;

use App\Changelog\Application\Announcement\AnnouncementAuthorizer;
use App\Changelog\Domain\Model\Announcement\AnnouncementRepository;
use App\Notifications\Application\Notification\Notify\SystemNotification;
use App\Notifications\Application\Notification\Notify\SystemNotifier;
use App\Notifications\Domain\Model\Notification\NotificationChannel;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use App\Security\Domain\Model\User\UserFilter;
use App\Security\Domain\Model\User\UserRepository;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;
use App\Shared\Domain\Model\Locale\LocaleGetter;

class NotifyAnnouncementService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private readonly AnnouncementRepository $repository,
        private readonly UserRepository $userRepository,
        private readonly SystemNotifier $notifier,
        private readonly AnnouncementAuthorizer $authorizer,
    ) {}

    public function __invoke(NotifyAnnouncementRequest $request, User $user): NotifyAnnouncementResponse
    {
        $announcement = $this->repository->find($request->announcementId);

        if (null == $announcement || !$announcement->isPublished()) {
            return new NotifyAnnouncementResponse(0);
        }

        $this->authorizer->denyUnlessCanDoThat($announcement, Action::Admin, $user);

        $recipientCount = 0;

        foreach ($this->userRepository->findByFilter(new UserFilter(limit: 0)) as $recipient) {
            $locale = $recipient->preferredLocale() ?? LocaleGetter::default();

            $this->notifier->notify(new SystemNotification(
                recipientId: $recipient->id(),
                channel: NotificationChannel::Internal,
                subject: 'Nueva novedad: {title}',
                template: '@changelog/announcements/published.notification.html.twig',
                params: [
                    'announcement' => $announcement,
                    'summary' => $announcement->summary($locale),
                ],
                accountId: $recipient->accountId(),
                subjectParams: ['{title}' => $announcement->title($locale)],
            ));

            ++$recipientCount;
        }

        return new NotifyAnnouncementResponse($recipientCount);
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof NotifyAnnouncementRequest;
    }
}
