<?php

declare(strict_types=1);

namespace App\Notifications\Application\Notification\Read;

use App\Notifications\Application\Notification\NotificationAuthorizer;
use App\Notifications\Domain\Model\Notification\NotificationId;
use App\Notifications\Domain\Model\Notification\NotificationNotFoundException;
use App\Notifications\Domain\Model\Notification\NotificationRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;

class MarkNotificationAsReadService
{
    public function __construct(
        private readonly NotificationRepository $notificationRepository,
        private readonly NotificationAuthorizer $authorizer,
    ) {}

    public function __invoke(MarkNotificationAsReadRequest $request, User $user): void
    {
        $notificationId = NotificationId::fromString($request->notificationId);

        $notification = $this->notificationRepository->find($notificationId);

        if (null === $notification) {
            throw new NotificationNotFoundException($notificationId);
        }

        $this->authorizer->denyUnlessCanDoThat($notification, SecurityAction::View, $user);

        $notification->markAsRead();

        $this->notificationRepository->add($notification);
    }
}
