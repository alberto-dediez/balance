<?php

declare(strict_types=1);

namespace App\Notifications\Application\Notification\Read;

use App\Notifications\Application\Notification\NotificationAuthorizer;
use App\Notifications\Domain\Model\Notification\NotificationId;
use App\Notifications\Domain\Model\Notification\NotificationNotFoundException;
use App\Notifications\Domain\Model\Notification\NotificationRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class MarkNotificationAsReadService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private readonly NotificationRepository $notificationRepository,
        private readonly NotificationAuthorizer $authorizer,
    ) {}

    public function __invoke(MarkNotificationAsReadRequest $request, User $user): MarkNotificationAsReadResponse
    {
        $notificationId = NotificationId::fromString($request->notificationId);

        $notification = $this->notificationRepository->find($notificationId);

        if (null === $notification) {
            throw new NotificationNotFoundException($notificationId);
        }

        $this->authorizer->denyUnlessCanDoThat($notification, SecurityAction::View, $user);

        $notification->markAsRead();

        $this->notificationRepository->add($notification);

        return new MarkNotificationAsReadResponse(
            notificationId: $notification->id(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof MarkNotificationAsReadRequest;
    }
}
